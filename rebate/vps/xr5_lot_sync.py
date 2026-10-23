"""
XRover 5 IB Rebates - MT5 lot sync for your own Windows VPS.

One MetaTrader 5 terminal per broker logs in to each customer account in turn with the
investor (read-only) password, reads closed trades, and sends lots per day to the website.

Setup: see README.txt next to this file (or Admin > Settings on the website).
Run:   python xr5_lot_sync.py           (one pass)
       python xr5_lot_sync.py --loop    (repeat every interval_minutes from config.json)
"""
import json, os, sys, time, ssl, urllib.request, urllib.error, traceback
from datetime import datetime, timedelta, timezone

HERE = os.path.dirname(os.path.abspath(__file__))
CONFIG = os.path.join(HERE, "config.json")
LOG = os.path.join(HERE, "sync.log")

try:
    import MetaTrader5 as mt5
except ImportError:
    print("The MetaTrader5 package is missing. Run:  pip install MetaTrader5")
    sys.exit(1)


def log(msg):
    line = datetime.now().strftime("%Y-%m-%d %H:%M:%S") + "  " + msg
    print(line)
    try:
        with open(LOG, "a", encoding="utf-8") as f:
            f.write(line + "\n")
        if os.path.getsize(LOG) > 2_000_000:          # keep the log small
            os.replace(LOG, LOG + ".old")
    except OSError:
        pass


def load_config():
    with open(CONFIG, encoding="utf-8") as f:
        cfg = json.load(f)
    if not cfg.get("token") or "PASTE" in cfg["token"]:
        raise SystemExit("Put your sync token from Admin > Settings into config.json")
    cfg["site_url"] = cfg.get("site_url", "https://xrover5.com/rebate").rstrip("/")
    return cfg


def ssl_context():
    """HTTPS trust: use Windows' own certificate check (truststore) which fetches missing root
    certificates automatically; fall back to the certifi bundle, then Python's default."""
    try:
        import truststore
        return truststore.SSLContext(ssl.PROTOCOL_TLS_CLIENT)
    except ImportError:
        pass
    try:
        import certifi
        return ssl.create_default_context(cafile=certifi.where())
    except ImportError:
        return ssl.create_default_context()


SSL_CTX = None


def api(cfg, action, query="", body=None):
    url = f"{cfg['site_url']}/api/lots.php?action={action}{query}"
    data = json.dumps(body).encode() if body is not None else None
    req = urllib.request.Request(url, data=data, method="POST" if data else "GET", headers={
        "X-Sync-Token": cfg["token"], "Content-Type": "application/json",
        "User-Agent": "XR5-LotSync/1.0", "Accept": "application/json"})
    try:
        global SSL_CTX
        SSL_CTX = SSL_CTX or ssl_context()
        with urllib.request.urlopen(req, timeout=60, context=SSL_CTX) as r:
            return json.loads(r.read().decode() or "{}")
    except urllib.error.HTTPError as e:
        raise RuntimeError(f"website said {e.code}: {e.read().decode(errors='replace')[:200]}")
    except urllib.error.URLError as e:
        if "CERTIFICATE_VERIFY_FAILED" in str(e):
            raise RuntimeError("HTTPS certificate check failed. Run:  pip install truststore certifi  then start again")
        raise


def read_lots(from_day):
    """Closed trades (exits) since from_day, grouped by day: round-turn lots, the usual IB commission basis."""
    start = datetime.strptime(from_day, "%Y-%m-%d").replace(tzinfo=timezone.utc)
    end = datetime.now(timezone.utc) + timedelta(days=1)
    deals = None
    for _ in range(3):                       # history can take a moment to load after login
        time.sleep(1.5)
        got = mt5.history_deals_get(start, end)
        if got is not None and (deals is None or len(got) >= len(deals)):
            deals = got
        if deals is not None and len(deals) > 0:
            break
    if deals is None:
        raise RuntimeError(f"history not available: {mt5.last_error()}")
    exits = {mt5.DEAL_ENTRY_OUT, mt5.DEAL_ENTRY_INOUT, mt5.DEAL_ENTRY_OUT_BY}
    trades = {mt5.DEAL_TYPE_BUY, mt5.DEAL_TYPE_SELL}
    days = {}
    for d in deals:
        if d.type not in trades or d.entry not in exits:
            continue
        day = datetime.fromtimestamp(d.time, timezone.utc).strftime("%Y-%m-%d")
        if day < from_day:
            continue
        v = days.setdefault(day, {"lots": 0.0, "deals": 0})
        v["lots"] = round(v["lots"] + d.volume, 2)
        v["deals"] += 1
    return days


def sync_terminal(cfg, term):
    name, path, bid = term.get("name", "broker"), term["path"], int(term["broker_id"])
    jobs = api(cfg, "jobs", f"&broker_id={bid}").get("accounts", [])
    if not jobs:
        log(f"{name}: no verified MT5 accounts")
        return
    if not os.path.exists(path):
        log(f"{name}: terminal not found at {path} - fix 'path' in config.json")
        return
    if not mt5.initialize(path=path, timeout=90_000):
        log(f"{name}: could not start MT5 terminal: {mt5.last_error()}")
        return
    log(f"{name}: {len(jobs)} account(s)")
    try:
        for j in jobs:
            acc = j["account_id"]
            try:
                if not mt5.login(int(j["login"]), password=j["password"], server=j["server"], timeout=60_000):
                    code, msg = mt5.last_error()
                    log(f"  {j['login']}: login failed ({code} {msg})")
                    api(cfg, "report", body={"account_id": acc, "error":
                        "Cannot log in - check investor password and server name"})
                    continue
                days = read_lots(j["from_day"])
                api(cfg, "report", body={"account_id": acc, "from_day": j["from_day"], "days": days})
                log(f"  {j['login']}: {sum(v['lots'] for v in days.values()):.2f} lots over {len(days)} day(s)")
            except Exception as e:
                log(f"  {j['login']}: error {e}")
    finally:
        mt5.shutdown()


def run_once(cfg):
    for term in cfg.get("terminals", []):
        try:
            sync_terminal(cfg, term)
        except Exception as e:
            log(f"{term.get('name', 'terminal')}: {e}")


def main():
    cfg = load_config()
    loop = "--loop" in sys.argv
    try:   # tell the website the sync has (re)started - admin gets an email
        import socket
        api(cfg, "hello", body={"host": socket.gethostname(), "interval_minutes": int(cfg.get("interval_minutes", 30))})
    except Exception as e:
        log(f"could not reach website: {e}")
    while True:
        log("sync started")
        try:
            run_once(cfg)
        except Exception:
            log(traceback.format_exc())
        log("sync finished")
        if not loop:
            break
        time.sleep(max(5, int(cfg.get("interval_minutes", 30))) * 60)


if __name__ == "__main__":
    main()
