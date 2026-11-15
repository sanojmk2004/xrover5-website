"""Builds the XRover Tech website: Home, Products, Services, Contact, 404, the blog and sitemap.xml.

Run from anywhere:  python3 _site/build.py
Blog posts live in _site/posts/*.html (see _site/BLOG.md). Generated files are overwritten on every build.
"""
import re, json, html, os, sys, glob, datetime

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
SITE = "https://xrover5.com"
BASE_CSS = "<style>\n" + open(os.path.join(HERE, "base.css"), encoding="utf-8").read() + "</style>"
GADS_TAG = open(os.path.join(HERE, "google-ads-tag.html"), encoding="utf-8").read().strip()   # Google Ads tag, in <head> of every page
FORM_JS = open(os.path.join(HERE, "contact-form.js"), encoding="utf-8").read()

PHONE = "+91 62383 77856"
EMAIL = "info@xrover5.com"
# "Get in touch" opens WhatsApp chat with a pre-filled message (page added by WA_JS below)
from urllib.parse import quote as _q
WA_NUMBER = "916238377856"
WA_TEXT = "Hi XRover Tech, I'm contacting you from your website xrover5.com"
WA_LINK = f"https://wa.me/{WA_NUMBER}?text={_q(WA_TEXT)}"
WA_ATTR = f'href="{WA_LINK}" target="_blank" rel="noopener" data-wa'
WA_ICON = '<svg class="wa-ic" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91A9.85 9.85 0 0 0 12.04 2zm5.78 14.03c-.24.68-1.42 1.3-1.95 1.35-.5.05-1.13.07-1.82-.11a16.6 16.6 0 0 1-1.65-.61 12.9 12.9 0 0 1-4.95-4.37c-.6-.8-1.29-2.13-1.29-3.4 0-1.27.67-1.9.9-2.16.24-.26.52-.32.7-.32h.5c.16 0 .38-.06.59.45.24.56.8 1.94.87 2.08.07.14.12.3.02.48-.09.18-.14.3-.28.46-.14.16-.29.36-.42.48-.14.14-.28.29-.12.57.16.28.72 1.19 1.55 1.93 1.07.95 1.97 1.24 2.25 1.38.28.14.45.12.61-.07.17-.19.7-.82.89-1.1.19-.28.37-.23.63-.14.26.09 1.64.77 1.92.91.28.14.47.21.54.33.07.12.07.68-.17 1.36z"/></svg>'
def wa_btn(label, topic=""):
    tp = f' data-topic="{html.escape(topic)}"' if topic else ""
    return f'<a class="btn btn-wa" {WA_ATTR}{tp}>{WA_ICON}{label}</a>'
WA_JS = """document.querySelectorAll('a[data-wa]').forEach(function(a){
  var pg = location.pathname === '/' ? '' : ' (page: ' + location.pathname + ')';
  var tp = a.dataset.topic ? ' about ' + a.dataset.topic : '';
  a.href = 'https://wa.me/""" + WA_NUMBER + """?text=' + encodeURIComponent(""" + json.dumps(WA_TEXT) + """ + tp + pg + '. ');
});"""

ORG = {"@type": "Organization", "@id": f"{SITE}/#org", "name": "XRover Tech", "url": SITE + "/",
       "logo": f"{SITE}/assets/favicon.png", "email": EMAIL, "telephone": "+916238377856",
       "description": "Software company building SaaS products, TradingView tools and custom business software.",
       "address": {"@type": "PostalAddress", "addressLocality": "Kannur", "addressRegion": "Kerala", "addressCountry": "IN"}}
WEBSITE = {"@type": "WebSite", "@id": f"{SITE}/#website", "url": SITE + "/", "name": "XRover Tech", "publisher": {"@id": f"{SITE}/#org"}}

CSS = """<style>
/* XRover Tech */
.btn-wa { background: #25d366; color: #06210f; border: 1px solid #25d366; display: inline-flex; align-items: center; gap: 8px; font-weight: 700; }
.btn-wa:hover { background: #3ee07a; border-color: #3ee07a; color: #06210f; }
.btn-wa .wa-ic { width: 19px; height: 19px; flex: none; }
.nav .links a.btn-wa, .nav .links a.btn-wa:hover { color: #06210f; background: #25d366; opacity: 1; }
.nav .links a.btn-wa:hover { background: #3ee07a; }
.wa-card { display: grid; gap: 16px; justify-items: start; background: linear-gradient(160deg, color-mix(in oklab, #25d366 14%, var(--surface-2)), var(--bg-2)); border: 1px solid color-mix(in oklab, #25d366 40%, var(--line)); border-radius: 18px; padding: clamp(22px, 4vw, 34px); }
.wa-card h2 { font-size: clamp(1.4rem, 3vw, 1.9rem); font-weight: 600; }
.wa-card p { color: var(--muted); }
.wa-card .btn-wa { font-size: 17px; padding: 14px 22px; }
.mods { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 250px), 1fr)); gap: 16px; margin-top: 8px; }
.mods .card h3 { display: flex; align-items: center; gap: 10px; }
.mods .card h3 i { width: 10px; height: 10px; border-radius: 3px; display: inline-block; }
.mods .card ul { margin: 0; padding-left: 1.1em; color: var(--muted); display: grid; gap: 6px; font-size: 15px; }
.hl { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 230px), 1fr)); gap: 14px; margin: 26px 0 10px; }
.hl div { background: color-mix(in oklab, #8b6cff 12%, var(--surface)); border: 1px solid color-mix(in oklab, #8b6cff 40%, var(--line)); border-radius: 14px; padding: 16px 18px; }
.hl b { display: block; font-size: 16px; margin-bottom: 4px; } .hl span { color: var(--muted); font-size: 14px; }
.wa { background: linear-gradient(170deg, var(--surface-2), var(--bg-2)); border: 1px solid var(--line); border-radius: 16px; padding: 16px; display: grid; gap: 10px; font-size: 14px; }
.wa .hd { display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px solid var(--line); }
.bub { max-width: 82%; padding: 10px 12px; border-radius: 12px; line-height: 1.45; }
.bub.in { background: var(--surface); border: 1px solid var(--line); justify-self: start; }
.bub.out { background: color-mix(in oklab, #25d366 22%, var(--surface)); border: 1px solid color-mix(in oklab, #25d366 40%, var(--line)); justify-self: end; }
.bub small { display: block; color: var(--muted); font-size: 11px; margin-top: 4px; }
.callbar { display: flex; gap: 10px; align-items: center; background: var(--bg-2); border: 1px solid var(--line); border-radius: 12px; padding: 10px 12px; }
.callbar .dot { width: 10px; height: 10px; border-radius: 50%; background: #25d366; box-shadow: 0 0 0 4px color-mix(in oklab, #25d366 25%, transparent); }
.wordmark { display: flex; align-items: center; gap: 10px; text-decoration: none; font-family: var(--display); font-weight: 800; font-size: 18px; letter-spacing: .01em; color: var(--fg); }
.wordmark svg { width: 34px; height: 34px; flex: none; }
.wordmark span b { color: var(--ember); font-weight: 800; }
.hero2 { padding-block: clamp(56px, 9vw, 110px) clamp(40px, 6vw, 72px); display: grid; grid-template-columns: 1.1fr .9fr; gap: clamp(28px, 5vw, 64px); align-items: center; }
@media (max-width: 900px) { .hero2 { grid-template-columns: 1fr; } }
.hero2 h1 { font-size: clamp(2.3rem, 5.6vw, 4.1rem); font-weight: 800; margin: 14px 0 18px; }
.hero2 h1 em { font-style: normal; background: linear-gradient(90deg, var(--ember-hot), var(--ember)); -webkit-background-clip: text; background-clip: text; color: transparent; }
.stack { background: linear-gradient(170deg, var(--surface-2), var(--bg-2)); border: 1px solid var(--line); border-radius: 20px; padding: 18px; display: grid; gap: 12px; box-shadow: 0 40px 80px -40px #000; min-width: 0; }
.stack .bar { display: flex; gap: 6px; padding: 2px 4px 8px; }
.stack .bar i { width: 10px; height: 10px; border-radius: 50%; background: var(--line); display: block; }
.prow { display: grid; grid-template-columns: auto 1fr auto; gap: 14px; align-items: center; background: var(--bg-2); border: 1px solid var(--line); border-radius: 14px; padding: 14px 16px; text-decoration: none; transition: border-color .2s, transform .2s; }
.prow:hover { border-color: color-mix(in oklab, var(--cyan) 50%, var(--line)); transform: translateX(3px); }
.picon { width: 42px; height: 42px; border-radius: 11px; display: grid; place-items: center; font: 800 15px var(--display); color: #0a0b12; }
.prow b { display: block; font-size: 15px; } .prow small { color: var(--muted); font-size: 13px; }
.pill { font: 500 11px var(--mono); letter-spacing: .08em; text-transform: uppercase; padding: 4px 9px; border-radius: 999px; border: 1px solid var(--line); color: var(--muted); white-space: nowrap; }
.pill.live { color: var(--bull); border-color: color-mix(in oklab, var(--bull) 45%, var(--line)); }
.pill.soon { color: var(--cyan); border-color: color-mix(in oklab, var(--cyan) 45%, var(--line)); }
.grid3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap: 20px; }
.grid2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap: 20px; }
.prod-card { position: relative; overflow: hidden; }
.prod-card .top { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
.prod-card .tags { display: flex; flex-wrap: wrap; gap: 6px; }
.prod-card .tags span { font: 500 12px var(--mono); color: var(--muted); background: var(--bg-2); border: 1px solid var(--line); border-radius: 6px; padding: 3px 8px; }
.prod-card a.more { color: var(--cyan); font-weight: 700; text-decoration: none; font-size: 15px; }
.svc .ico { width: 40px; height: 40px; border-radius: 10px; background: var(--surface-2); border: 1px solid var(--line); display: grid; place-items: center; color: var(--ember); }
.svc .ico svg { width: 20px; height: 20px; }
.steps { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr)); gap: 16px; counter-reset: s; }
.steps .card { counter-increment: s; }
.steps .card::before { content: "0" counter(s); font: 500 13px var(--mono); color: var(--cyan); letter-spacing: .1em; }
.product { display: grid; grid-template-columns: 1fr 1fr; gap: clamp(24px, 4vw, 52px); align-items: start; scroll-margin-top: 90px; }
@media (max-width: 900px) { .product { grid-template-columns: 1fr; } }
.product h2 { font-size: clamp(1.7rem, 3.4vw, 2.4rem); font-weight: 600; margin: 10px 0 14px; }
.ticks { list-style: none; padding: 0; margin: 18px 0 0; display: grid; gap: 10px; }
.ticks li { padding-left: 28px; position: relative; color: #cfd3e3; }
.ticks li::before { content: ""; position: absolute; left: 0; top: .45em; width: 14px; height: 8px; border-left: 2px solid var(--bull); border-bottom: 2px solid var(--bull); transform: rotate(-45deg); }
.shot { border-radius: 16px; border: 1px solid var(--line); overflow: hidden; background: var(--bg-2); }
.shot img { display: block; width: 100%; height: auto; }
.mock { background: linear-gradient(170deg, var(--surface-2), var(--bg-2)); border: 1px solid var(--line); border-radius: 16px; padding: 18px; display: grid; gap: 10px; font-size: 14px; }
.mock .row { display: flex; justify-content: space-between; gap: 12px; align-items: center; background: var(--bg-2); border: 1px solid var(--line); border-radius: 10px; padding: 12px 14px; }
.ok { color: var(--bull); font-weight: 700; } .warn { color: var(--ember); font-weight: 700; } .mono { font-family: var(--mono); font-size: 13px; color: var(--muted); }
.price-row { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 22px; }
.price { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; padding: 14px 18px; }
.price b { font: 700 1.3rem var(--display); } .price small { display: block; color: var(--muted); font-size: 13px; }
.note-sm { margin-top: 18px; font-size: 13px; color: var(--muted); max-width: 62ch; }
.faq-list { display: grid; gap: 12px; max-width: 820px; }
.faq-list details { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; padding: 16px 20px; }
.faq-list summary { cursor: pointer; font-weight: 700; }
.faq-list p { color: var(--muted); margin-top: 10px; }
.cta2 { text-align: center; display: grid; gap: 16px; justify-items: center; }
.cta2 h2 { font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 600; }
.foot .about { max-width: 46ch; }
</style>"""

MARK = """<svg viewBox="0 0 40 40" aria-hidden="true"><defs><linearGradient id="xg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#45cfff"/><stop offset="1" stop-color="#2f8fff"/></linearGradient></defs><rect x="1" y="1" width="38" height="38" rx="10" fill="#151823" stroke="#272c3d"/><path d="M11 11l18 18M29 11L11 29" stroke="url(#xg)" stroke-width="4.5" stroke-linecap="round"/><circle cx="29" cy="11" r="3" fill="#ffa84a"/></svg>"""

NAV = [("Home", "/"), ("Products", "/products/"), ("Services", "/services/"), ("Blog", "/blog/"), ("Contact Us", "/contact/")]

def header(cur):
    links = "\n      ".join(f'<a href="{u}"{" aria-current=\"page\"" if u == cur else ""}>{n}</a>' for n, u in NAV)
    return f"""<header class="nav">
  <div class="wrap nav-inner">
    <a class="wordmark" href="/" aria-label="XRover Tech home">{MARK}<span>XRover <b>Tech</b></span></a>
    <button class="menu-btn" id="menuBtn" aria-expanded="false" aria-controls="links">Menu</button>
    <nav class="links" id="links" aria-label="Main">
      {links}
      {wa_btn("Chat on WhatsApp")}
    </nav>
  </div>
</header>"""

FOOTER = f"""<footer>
  <div class="wrap">
    <div class="foot">
      <div class="about">
        <a class="wordmark" href="/" aria-label="XRover Tech home">{MARK}<span>XRover <b>Tech</b></span></a>
        <small>XRover Tech builds software products and custom business software. Kannur, Kerala, India.</small>
      </div>
      <nav aria-label="Footer">
        <a href="/">Home</a><a href="/products/">Products</a><a href="/services/">Services</a><a href="/blog/">Blog</a><a href="/contact/">Contact Us</a><a href="mailto:{EMAIL}">{EMAIL}</a>
      </nav>
      <small style="margin:0">© 2026 XRover Tech. All rights reserved.</small>
    </div>
  </div>
</footer>"""

MENU_JS = """(function(){var b=document.getElementById('menuBtn'),l=document.getElementById('links');
b.addEventListener('click',function(){var o=l.classList.toggle('open');b.setAttribute('aria-expanded',String(o));});})();"""

def page(path, title, desc, body, graph_extra=(), js="", noindex=False, ogtype="website", head_extra="", webpage=True):
    url = SITE + path
    t, d = html.escape(title), html.escape(desc)
    graph = [ORG, WEBSITE] + ([{"@type": "WebPage", "@id": url, "url": url, "name": title, "description": desc,
                            "isPartOf": {"@id": f"{SITE}/#website"}, "about": {"@id": f"{SITE}/#org"}}] if webpage else []) + list(graph_extra)
    ld = json.dumps({"@context": "https://schema.org", "@graph": graph}, indent=1, ensure_ascii=False)
    robots = "noindex" if noindex else "index, follow, max-image-preview:large"
    return f"""<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
{GADS_TAG}
<title>{t}</title>
<meta name="description" content="{d}">
<link rel="canonical" href="{url}">
<meta name="robots" content="{robots}">
<meta name="theme-color" content="#0a0b12">
<meta property="og:type" content="{ogtype}">
<meta property="og:site_name" content="XRover Tech">
<meta property="og:url" content="{url}">
<meta property="og:title" content="{t}">
<meta property="og:description" content="{d}">
<meta property="og:image" content="{SITE}/assets/og-xrover-tech.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{t}">
<meta name="twitter:description" content="{d}">
<meta name="twitter:image" content="{SITE}/assets/og-xrover-tech.jpg">
<link rel="icon" type="image/png" href="/assets/favicon.png">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
<script type="application/ld+json">
{ld}
</script>
<style>html{{color-scheme:dark}}body{{margin:0}}img{{max-width:100%}}[hidden]{{display:none!important}}:root{{padding-top:env(safe-area-inset-top,0px);padding-bottom:env(safe-area-inset-bottom,0px)}}</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Unbounded:wght@400;600;800&family=Manrope:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap">
{BASE_CSS}
{CSS}
{head_extra}
</head>
<body>
{header("/blog/" if path.startswith("/blog/") else (path if path in [u for _, u in NAV] else ""))}

<main>
<div class="page">
{body}
</div>
</main>

{FOOTER}

<script>
{MENU_JS}
{WA_JS}
(function(){{
{js}
}})();
</script>
</body>
</html>
"""

ICON = {
 "code": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6l-6 6 6 6M16 6l6 6-6 6"/></svg>',
 "plug": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 2v6M15 2v6M6 8h12v4a6 6 0 0 1-12 0zM12 18v4"/></svg>',
 "chart": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18M7 15l4-4 3 3 5-6"/></svg>',
 "users": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M21.5 20a6.5 6.5 0 0 0-4-6"/></svg>',
 "spark": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9z"/></svg>',
 "shield": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></svg>',
}

PRODUCTS = [
 dict(id="crm", name="XRover CRM", color="linear-gradient(135deg,#c9b6ff,#8b6cff)", ab="CR", status=("live", "Live"),
      short="All-in-one business operations suite",
      blurb="Leads, sales, HR and accounts in one system, with WhatsApp Business API messaging, WhatsApp voice calls and automated follow-ups built in.",
      tags=["WhatsApp Business API", "HR & payroll", "Accounts", "Automation"]),
 dict(id="xrover5", name="XRover 5", color="linear-gradient(135deg,#ffd394,#ffa84a)", ab="X5", status=("live", "Live"),
      short="Chart-analysis software for TradingView",
      blurb="A TradingView indicator that analyses candlestick data in real time and maps zones, levels and alerts directly on the chart, with Telegram notifications.",
      tags=["TradingView", "Pine Script", "Telegram alerts"]),
 dict(id="adclearx", name="AdClearX", color="linear-gradient(135deg,#7ef0c3,#34d399)", ab="AC", status=("soon", "Early access"),
      short="Ad policy & creative checker for Meta ads",
      blurb="Checks video, poster and text ads against Meta advertising policies before you publish, and suggests improvements to scripts and creatives.",
      tags=["SaaS", "Meta Marketing API", "AI"]),
 dict(id="mt5-connector", name="TradingView → MT5 Connector", color="linear-gradient(135deg,#8fd3ff,#2f8fff)", ab="MT", status=("soon", "Coming soon"),
      short="Webhook automation between TradingView and MetaTrader 5",
      blurb="Connects TradingView alert webhooks to MetaTrader 5 through a cloud relay and a licensed MT5 add-on, with logs and a status dashboard.",
      tags=["Webhooks", "Cloud relay", "MT5 add-on"]),
]

def prow(p):
    return f"""<a class="prow" href="/products/#{p['id']}"><span class="picon" style="background:{p['color']}">{p['ab']}</span><span><b>{p['name']}</b><small>{p['short']}</small></span><span class="pill {p['status'][0]}">{p['status'][1]}</span></a>"""

def pcard(p):
    tags = "".join(f"<span>{t}</span>" for t in p["tags"])
    return f"""<article class="card prod-card">
        <div class="top"><span class="picon" style="background:{p['color']}">{p['ab']}</span><span class="pill {p['status'][0]}">{p['status'][1]}</span></div>
        <h3>{p['name']}</h3>
        <p><strong style="color:var(--fg)">{p['short']}.</strong> {p['blurb']}</p>
        <div class="tags">{tags}</div>
        <a class="more" href="/products/#{p['id']}">Learn more →</a>
      </article>"""

SERVICES = [
 ("code", "Web apps & SaaS", "Custom web applications and subscription software, from first prototype to a production-ready product with accounts, payments and admin panels."),
 ("plug", "Automation & integrations", "Webhooks, REST APIs and background jobs that connect the tools you already use, so data moves between systems without manual work."),
 ("chart", "TradingView Pine Script development", "Custom indicators, dashboards and alert logic for TradingView, including invite-only publishing and alert delivery to Telegram."),
 ("users", "CRM & business tools", "Lead management, sales-team workflows, WhatsApp and email integrations, and internal dashboards built around how your team works."),
 ("spark", "AI-powered features", "Practical AI inside your product: content checks, suggestions, summaries and assistants built on modern language models."),
 ("shield", "Maintenance & support", "Hosting setup, monitoring, updates and ongoing improvements for the software we build and run with you."),
]

def svc_cards():
    return "\n".join(f"""<article class="card svc"><span class="ico">{ICON[i]}</span><h3>{t}</h3><p>{d}</p></article>""" for i, t, d in SERVICES)

# ---------------------------------------------------------------- HOME
def home_body(LATEST_SECTION): return f"""  <div class="wrap hero2">
    <div>
      <span class="eyebrow">XRover Tech · Software company · Kerala, India</span>
      <h1>We build software that <em>automates real work.</em></h1>
      <p class="lead">XRover Tech designs, builds and supports business software: an all-in-one CRM with WhatsApp Business API, SaaS products, TradingView tools and custom applications.</p>
      <div class="btn-row" style="margin-top:28px"><a class="btn btn-primary" href="/products/">Explore our products</a>{wa_btn("Start a project", "a software project")}</div>
    </div>
    <div class="stack" aria-label="XRover Tech products">
      <div class="bar"><i></i><i></i><i></i></div>
      {''.join(prow(p) for p in PRODUCTS)}
    </div>
  </div>

  <section class="band">
    <div class="wrap">
      <div class="band-head"><span class="eyebrow">Our products</span><h2>Software we build and run</h2>
        <p class="lead">Each product is designed, developed and supported in-house by the XRover Tech team.</p></div>
      <div class="grid2">
      {''.join(pcard(p) for p in PRODUCTS)}
      </div>
    </div>
  </section>

  <section class="band">
    <div class="wrap">
      <div class="band-head"><span class="eyebrow">Services</span><h2>Custom software development</h2>
        <p class="lead">We also build software for other businesses, using the same stack and experience behind our own products.</p></div>
      <div class="grid3">
{svc_cards()}
      </div>
    </div>
  </section>

  <section class="band">
    <div class="wrap">
      <div class="band-head"><span class="eyebrow">How we work</span><h2>From idea to running software</h2></div>
      <div class="steps">
        <div class="card"><h3>Discover</h3><p>We understand the problem, the users and what success looks like.</p></div>
        <div class="card"><h3>Design</h3><p>Clear screens, data flow and a plan you can review before we build.</p></div>
        <div class="card"><h3>Build</h3><p>Working releases early and often, so you see progress every week.</p></div>
        <div class="card"><h3>Launch & support</h3><p>Deployment, monitoring and improvements after go-live.</p></div>
      </div>
    </div>
  </section>

{LATEST_SECTION}
  <section class="band">
    <div class="wrap cta2">
      <span class="eyebrow">Let's talk</span>
      <h2>Have a product idea or a process to automate?</h2>
      <p class="lead">Tell us what you need. We'll reply with next steps, usually within one business day.</p>
      <div class="btn-row" style="justify-content:center">{wa_btn("Get in touch")}<a class="btn btn-ghost" href="/services/">See services</a></div>
    </div>
  </section>"""

# ---------------------------------------------------------------- PRODUCTS
def ticks(items): return '<ul class="ticks">' + "".join(f"<li>{i}</li>" for i in items) + "</ul>"
products = f"""  <div class="wrap page-head">
    <span class="eyebrow">Products</span>
    <h1>Our software products</h1>
    <p class="lead">Business software and SaaS products designed, built and supported by XRover Tech.</p>
    <div class="btn-row">{''.join(f'<a class="btn btn-ghost" href="#{p["id"]}">{p["name"]}</a>' for p in PRODUCTS)}</div>
  </div>

  <section class="band" id="crm" style="padding-top:36px">
    <div class="wrap product">
      <div>
        <span class="pill live">Live</span>
        <h2>XRover CRM</h2>
        <p class="lead">An all-in-one solution for complete business operations: lead management, sales, HR and accounts in one place, with the WhatsApp Business API built in.</p>
        <div class="hl">
          <div><b>WhatsApp voice chat & calls</b><span>Talk to customers by WhatsApp voice message or voice call, right from the CRM.</span></div>
          <div><b>Automated follow-ups</b><span>Scheduled WhatsApp follow-ups and reminders, so no lead goes cold.</span></div>
          <div><b>WhatsApp chatbot</b><span>Instant auto-replies with buttons and AI until your team takes over.</span></div>
          <div><b>One shared inbox</b><span>WhatsApp chats and company email in one place for your team.</span></div>
        </div>
        <div class="btn-row" style="margin-top:12px">{wa_btn("Book a CRM demo", "an XRover CRM demo")}</div>
      </div>
      <div class="wa" aria-hidden="true">
        <div class="hd"><b>WhatsApp · New lead</b><span class="pill live">Auto-assigned</span></div>
        <div class="bub in">Hi, I saw your ad. Can you share the details?<small>10:02</small></div>
        <div class="bub out">Hello Anil 👋 Thanks for reaching out! Choose an option below.<small>10:02 · Auto-reply</small></div>
        <div class="bub in">▶ ━━━━━━━━ 0:18 &nbsp;Voice message<small>10:05</small></div>
        <div class="callbar"><span class="dot"></span><span><b>WhatsApp voice call</b><br><span class="mono">Rahul (Sales) · 03:42</span></span></div>
        <div class="bub out">Follow-up scheduled for tomorrow, 11:00 AM ✓<small>Automation</small></div>
      </div>
    </div>
    <div class="wrap">
      <div class="mods" style="margin-top:44px">
        <div class="card"><h3><i style="background:#8b6cff"></i>Lead management</h3><ul>
          <li>Leads from Meta lead ads, Click-to-WhatsApp ads, YouTube and website forms</li>
          <li>Automatic lead distribution based on staff attendance</li>
          <li>Lead stages, notes, reminders and full history</li>
          <li>Unattended leads reassigned automatically in office hours</li></ul></div>
        <div class="card"><h3><i style="background:#25d366"></i>WhatsApp Business API</h3><ul>
          <li>Official WhatsApp Cloud API integration</li>
          <li>WhatsApp voice messages and voice calls</li>
          <li>Chatbot with buttons, AI replies and language choice</li>
          <li>Automated follow-ups, welcome messages and broadcasts</li></ul></div>
        <div class="card"><h3><i style="background:#45cfff"></i>HR management</h3><ul>
          <li>Attendance with face scan and location</li>
          <li>Salary plans, targets and incentives</li>
          <li>Roles for admins, managers and staff</li>
          <li>Staff performance dashboards</li></ul></div>
        <div class="card"><h3><i style="background:#ffa84a"></i>Accounts management</h3><ul>
          <li>Income, expenses and payment records</li>
          <li>Payment links and payment-proof approvals</li>
          <li>Partner and investment tracking with monthly summaries</li>
          <li>Reports for owners and admins</li></ul></div>
        <div class="card"><h3><i style="background:#fb6f84"></i>Customer app</h3><ul>
          <li>Customer portal with in-app videos</li>
          <li>Automatic renewal and expiry reminders</li>
          <li>In-app chat and free browser voice calls</li>
          <li>Terms acceptance and quizzes before access</li></ul></div>
        <div class="card"><h3><i style="background:#34d399"></i>Communication & inbox</h3><ul>
          <li>Company email inbox inside the CRM</li>
          <li>Team chat history on every customer</li>
          <li>Admin-only visibility controls</li>
          <li>Notifications for new leads and tasks</li></ul></div>
      </div>
    </div>
  </section>

  <section class="band" id="xrover5">
    <div class="wrap product">
      <div>
        <span class="pill live">Live</span>
        <h2>XRover 5</h2>
        <p class="lead">Chart-analysis software for TradingView. XRover 5 reads candlestick data in real time and draws its analysis directly on the chart: adaptive zones, reference levels, a status dashboard and timed alerts.</p>
        {ticks(["Real-time candlestick data analysis inside TradingView",
                "Adaptive zones that update as new candles form",
                "Two display modes: Smart Mode and Professional Mode",
                "On-chart dashboard with a built-in template library",
                "Event countdown and session timers",
                "Alerts in TradingView and on Telegram"])}
        <div class="price-row">
          <div class="price"><b>USD 499</b><small>per month, full software access</small></div>
          <div class="price"><b>USD 9</b><small>one-time live product demo</small></div>
        </div>
        <div class="btn-row" style="margin-top:22px">{wa_btn("Book a demo", "an XRover 5 demo")}</div>
        <p class="note-sm">XRover 5 is delivered as an invite-only TradingView script. It is analysis software only: it does not give advice, hold funds or place orders.</p>
      </div>
      <div class="shot"><img src="/assets/xrover5-chart.webp" alt="XRover 5 indicator running on a TradingView chart with its on-chart zones and dashboard" width="1790" height="828" loading="lazy"></div>
    </div>
  </section>

  <section class="band" id="adclearx">
    <div class="wrap product">
      <div>
        <span class="pill soon">Early access</span>
        <h2>AdClearX</h2>
        <p class="lead">An ad policy and creative checker for Meta (Facebook & Instagram) ads. Catch policy problems before you publish and get clear suggestions to improve each ad.</p>
        {ticks(["Checks video, poster and text ads against Meta advertising policies",
                "Flags risky wording and claims with plain-language explanations",
                "Suggests improvements to ad scripts and creatives",
                "Connects directly to your Meta ad accounts",
                "AI-assisted ad setup from uploaded videos"])}
        <div class="btn-row" style="margin-top:22px">{wa_btn("Request early access", "AdClearX early access")}</div>
      </div>
      <div class="mock" aria-hidden="true">
        <div class="row"><span>video_ad_v3.mp4</span><span class="ok">Policy check passed</span></div>
        <div class="row"><span>poster_offer.png</span><span class="warn">2 suggestions</span></div>
        <div class="row"><span class="mono">"Limited seats, book today"</span><span class="ok">OK</span></div>
        <div class="row"><span class="mono">Text on image: 18%</span><span class="ok">Good</span></div>
        <div class="row"><span>Creative score</span><span class="ok">86 / 100</span></div>
      </div>
    </div>
  </section>

  <section class="band" id="mt5-connector">
    <div class="wrap product">
      <div>
        <span class="pill soon">Coming soon</span>
        <h2>TradingView → MT5 Connector</h2>
        <p class="lead">Webhook automation between TradingView and MetaTrader 5. Alerts from TradingView are received by a cloud relay and passed to a licensed MT5 add-on running in the user's own terminal.</p>
        {ticks(["Receives TradingView alert webhooks through a secure cloud relay",
                "MT5 add-on with licence-key activation",
                "Runs in the user's own MetaTrader 5 terminal or VPS",
                "Activity logs, retries and connection status",
                "Simple setup with no coding required"])}
        <div class="btn-row" style="margin-top:22px">{wa_btn("Join the waiting list", "the TradingView to MT5 Connector waiting list")}</div>
        <p class="note-sm">The connector is automation software. Users configure and control their own MetaTrader 5 accounts and settings.</p>
      </div>
      <div class="mock" aria-hidden="true">
        <div class="row"><span class="mono">POST /hook/••••</span><span class="ok">200 received</span></div>
        <div class="row"><span>Relay → MT5 add-on</span><span class="ok">Delivered 82 ms</span></div>
        <div class="row"><span>Licence</span><span class="ok">Active</span></div>
        <div class="row"><span>Terminal status</span><span class="ok">Connected</span></div>
      </div>
    </div>
  </section>

  <section class="band">
    <div class="wrap cta2">
      <h2>Want a demo or early access?</h2>
      <div class="btn-row" style="justify-content:center">{wa_btn("Contact us")}</div>
    </div>
  </section>"""
prod_ld = [{"@type": "ItemList", "name": "XRover Tech products", "itemListElement": [
    {"@type": "ListItem", "position": i + 1, "item": {"@type": "SoftwareApplication", "name": p["name"], "description": p["blurb"],
     "applicationCategory": "BusinessApplication", "operatingSystem": "Web", "url": f"{SITE}/products/#{p['id']}",
     "publisher": {"@id": f"{SITE}/#org"}}} for i, p in enumerate(PRODUCTS)]}]

# ---------------------------------------------------------------- SERVICES
FAQ = [
 ("What does XRover Tech do?", "XRover Tech is a software company. We build our own software products, including XRover CRM, XRover 5, AdClearX and the TradingView to MT5 Connector, and we develop custom software for other businesses."),
 ("Do you build custom software for other companies?", "Yes. We build web applications, SaaS products, automations, integrations, CRM tools and TradingView Pine Script tools for clients."),
 ("Where is XRover Tech based?", "We are based in Kannur, Kerala, India, and work with clients remotely."),
 ("How do we start a project?", "Send us a message with a short description of what you need. We'll reply with questions and next steps, usually within one business day."),
 ("Do you provide support after launch?", "Yes. We offer hosting setup, monitoring, updates and ongoing improvements for the software we build."),
]
services = f"""  <div class="wrap page-head">
    <span class="eyebrow">Services</span>
    <h1>Custom software development</h1>
    <p class="lead">The same team and stack behind our own products, working on yours: web apps, SaaS, automation, integrations and TradingView tools.</p>
    <div class="btn-row">{wa_btn("Start a project", "a software project")}</div>
  </div>

  <section class="band" style="padding-top:36px">
    <div class="wrap">
      <div class="grid3">
{svc_cards()}
      </div>
    </div>
  </section>

  <section class="band">
    <div class="wrap">
      <div class="band-head"><span class="eyebrow">Technology</span><h2>What we work with</h2></div>
      <div class="grid2">
        <div class="card"><h3>Web & backend</h3><p>PHP, Python, Node.js, MySQL and PostgreSQL, REST APIs and webhooks.</p></div>
        <div class="card"><h3>Front end</h3><p>Fast, responsive interfaces that work well on phones and desktops.</p></div>
        <div class="card"><h3>Platforms & APIs</h3><p>Meta Marketing and WhatsApp Cloud APIs, Telegram bots, payment gateways, TradingView and MetaTrader 5.</p></div>
        <div class="card"><h3>Cloud & hosting</h3><p>cPanel hosting, VPS and cloud servers, scheduled jobs, monitoring and email alerts.</p></div>
      </div>
    </div>
  </section>

  <section class="band">
    <div class="wrap">
      <div class="band-head"><span class="eyebrow">How we work</span><h2>A simple, transparent process</h2></div>
      <div class="steps">
        <div class="card"><h3>Discover</h3><p>We understand the problem, the users and what success looks like.</p></div>
        <div class="card"><h3>Design</h3><p>Clear screens, data flow and a plan you can review before we build.</p></div>
        <div class="card"><h3>Build</h3><p>Working releases early and often, so you see progress every week.</p></div>
        <div class="card"><h3>Launch & support</h3><p>Deployment, monitoring and improvements after go-live.</p></div>
      </div>
    </div>
  </section>

  <section class="band">
    <div class="wrap">
      <div class="band-head"><span class="eyebrow">FAQ</span><h2>Frequently asked questions</h2></div>
      <div class="faq-list">
      {''.join(f'<details><summary>{html.escape(q)}</summary><p>{html.escape(a)}</p></details>' for q, a in FAQ)}
      </div>
    </div>
  </section>

  <section class="band">
    <div class="wrap cta2">
      <h2>Tell us what you want to build</h2>
      <div class="btn-row" style="justify-content:center">{wa_btn("Get in touch")}</div>
    </div>
  </section>"""
faq_ld = [{"@type": "FAQPage", "mainEntity": [{"@type": "Question", "name": q, "acceptedAnswer": {"@type": "Answer", "text": a}} for q, a in FAQ]}]

# ---------------------------------------------------------------- CONTACT
contact = f"""  <div class="wrap page-head">
    <span class="eyebrow">Contact Us</span>
    <h1>Talk to XRover Tech</h1>
    <p class="lead">Ask about our products, book a product demo or tell us about a software project. Chat with us on WhatsApp or send an email.</p>
  </div>

  <section class="band" style="padding-top:36px">
    <div class="wrap contact-grid">
      <div class="wa-card">
        <span class="eyebrow">Fastest reply</span>
        <h2>Chat with us on WhatsApp</h2>
        <p>Ask about our products, book a demo or tell us about your project. Tap the button and your message to XRover Tech opens in WhatsApp.</p>
        {wa_btn("Chat on WhatsApp")}
        <small style="color:var(--muted)">WhatsApp {PHONE}</small>
      </div>

      <div class="info">
        <div class="card"><span class="k">Email</span><span class="v" id="cEmail">{EMAIL}</span><button class="copy" type="button" data-copy="cEmail">Copy</button></div>
        <div class="card"><span class="k">WhatsApp</span><span class="v" id="cPhone">{PHONE}</span><button class="copy" type="button" data-copy="cPhone">Copy</button></div>
        <div class="card"><span class="k">Location</span><span class="v">Kannur, Kerala, India</span></div>
        <div class="card"><span class="k">Company</span><span class="v">XRover Tech</span><small style="color:var(--muted)">Software products & custom development</small></div>
      </div>
    </div>
  </section>"""
COPY_JS = """  function selectText(el) { const r = document.createRange(); r.selectNodeContents(el); const s = getSelection(); s.removeAllRanges(); s.addRange(r); }
  document.querySelectorAll('.copy').forEach(b => b.addEventListener('click', () => {
    const el = document.getElementById(b.dataset.copy);
    const done = () => { b.textContent = 'Copied'; setTimeout(() => b.textContent = 'Copy', 1500); };
    try { navigator.clipboard.writeText(el.textContent).then(done, () => selectText(el)); } catch (e) { selectText(el); }
  }));
"""
contact_ld = [{"@type": "ContactPage", "url": f"{SITE}/contact/", "name": "Contact XRover Tech"}]

# ---------------------------------------------------------------- 404
nf = """  <div class="wrap page-head">
    <span class="eyebrow">Error 404</span>
    <h1>This page could not be found</h1>
    <p class="lead">The link may be old or mistyped. Try one of these instead.</p>
    <div class="btn-row"><a class="btn btn-primary" href="/">Home</a><a class="btn btn-ghost" href="/products/">Products</a><a class="btn btn-ghost" href="/services/">Services</a>{wa_btn("Contact us")}</div>
  </div>
  <section class="band" style="padding-top:20px"><div class="wrap"></div></section>"""

def write(rel, s):
    os.makedirs(os.path.dirname(f"{ROOT}/{rel}") or ROOT, exist_ok=True)
    open(f"{ROOT}/{rel}", "w", encoding="utf-8").write(s)

exec(open(os.path.join(HERE, "blog.py"), encoding="utf-8").read())   # defines POSTS, blog pages, LATEST_SECTION

write("index.html", page("/", "XRover Tech – Software Products & Custom Software Development",
      "XRover Tech is a software company in Kerala, India. Products: XRover CRM with WhatsApp Business API, HR and accounts, plus XRover 5, AdClearX and a TradingView to MT5 Connector.", home_body(LATEST_SECTION)))
write("products/index.html", page("/products/", "Products – XRover CRM, XRover 5, AdClearX & MT5 Connector | XRover Tech",
      "XRover CRM: leads, HR, accounts and WhatsApp Business API with voice calls and automated follow-ups. Plus XRover 5, AdClearX and the TradingView to MT5 Connector.", products, prod_ld))
write("services/index.html", page("/services/", "Custom Software Development Services | XRover Tech",
      "Web apps, SaaS, automation and API integrations, CRM tools, AI features and TradingView Pine Script development by XRover Tech, Kerala, India.", services, faq_ld))
write("contact/index.html", page("/contact/", "Contact XRover Tech – Software Company, Kerala, India",
      "Contact XRover Tech for product demos, early access or custom software projects. Email info@xrover5.com or chat on WhatsApp +91 62383 77856.", contact, contact_ld, js=COPY_JS))
write("404.html", page("/404.html", "Page not found | XRover Tech", "Page not found.", nf, noindex=True))
# Short link: https://xrover5.com/whatsapp  -> opens WhatsApp chat (optional ?from=instagram adds the source to the message)
_wa_page = f"""<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
{GADS_TAG}
<title>Chat with XRover Tech on WhatsApp</title>
<meta name="robots" content="noindex">
<meta name="description" content="Chat with XRover Tech on WhatsApp.">
<meta property="og:title" content="Chat with XRover Tech on WhatsApp">
<meta property="og:description" content="Tap to start a WhatsApp chat with XRover Tech.">
<meta property="og:image" content="{SITE}/assets/og-xrover-tech.jpg">
<meta http-equiv="refresh" content="2;url={html.escape(WA_LINK)}">
<link rel="icon" type="image/png" href="/assets/favicon.png">
<style>
:root {{ color-scheme: dark; }}
body {{ margin: 0; min-height: 100vh; display: grid; place-items: center; background: #0a0b12; color: #eceef6; font: 16px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; text-align: center; padding: 24px; box-sizing: border-box; }}
a.btn {{ display: inline-flex; gap: 8px; align-items: center; background: #25d366; color: #06210f; font-weight: 700; text-decoration: none; padding: 14px 22px; border-radius: 999px; margin-top: 18px; }}
a.btn svg {{ width: 20px; height: 20px; }}
p {{ color: #9aa0b6; }}
</style>
</head>
<body>
<main>
  <h1 style="font-size:1.4rem;margin:0">Opening WhatsApp…</h1>
  <p>If WhatsApp does not open automatically, tap the button.</p>
  <a class="btn" id="go" href="{html.escape(WA_LINK)}">{WA_ICON}Chat on WhatsApp</a>
</main>
<script>
(function(){{
  var q = new URLSearchParams(location.search), src = (q.get('from') || '').replace(/[^A-Za-z0-9_ .-]/g, '').slice(0, 40);
  var url = 'https://wa.me/{WA_NUMBER}?text=' + encodeURIComponent({json.dumps(WA_TEXT)} + (src ? ' (via ' + src + ')' : '') + '. ');
  document.getElementById('go').href = url;
  var done = false, go = function () {{ if (!done) {{ done = true; location.replace(url); }} }};
  try {{ gtag('event', 'conversion', {{ 'send_to': 'AW-18549387221/X6LGCKHd_rgdENXfhI1F', 'transport_type': 'beacon', 'event_callback': go }}); }} catch (e) {{}}
  setTimeout(go, 800);
}})();
</script>
</body>
</html>
"""
write("whatsapp/index.html", _wa_page)

write("sitemap.xml", SITEMAP)
print("built: 4 pages, 404, blog", len(POSTS), "posts")
