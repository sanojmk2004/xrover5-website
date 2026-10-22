"""Builds the blog from _blog/posts/*.html.
Run from anywhere:  python3 _blog/build.py
Generates blog/index.html, blog/<slug>/index.html and the blog entries in sitemap.xml.
Uses the stylesheet, header and footer of contact/index.html so the blog matches the site."""
import os, re, sys, json, html, glob, datetime

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
REQUIRED = ("slug", "category", "title", "description", "date")

def load():
    posts = []
    for f in sorted(glob.glob(os.path.join(ROOT, "_blog", "posts", "*.html"))):
        raw = open(f, encoding="utf-8").read()
        m = re.match(r"\s*<!--(.*?)-->(.*)", raw, re.S)
        if not m: sys.exit(f"{f}: missing <!-- front matter -->")
        meta = dict(re.findall(r"^\s*(\w+):\s*(.+?)\s*$", m.group(1), re.M))
        miss = [k for k in REQUIRED if k not in meta]
        if miss: sys.exit(f"{f}: missing {miss}")
        if not re.fullmatch(r"[a-z0-9-]+", meta["slug"]): sys.exit(f"{f}: bad slug")
        d = datetime.date.fromisoformat(meta["date"])
        posts.append({"slug": meta["slug"], "cat": meta["category"], "title": meta["title"], "desc": meta["description"],
                      "date": d.isoformat(), "date_h": d.strftime("%-d %B %Y"), "body": m.group(2),
                      "brokers": meta.get("brokers", "").lower() in ("yes", "true", "1")})
    slugs = [p["slug"] for p in posts]
    dup = {s for s in slugs if slugs.count(s) > 1}
    if dup: sys.exit(f"duplicate slugs: {dup}")
    posts.sort(key=lambda p: (p["date"], p["slug"]), reverse=True)
    return posts

POSTS = load()
src = open(f"{ROOT}/contact/index.html", encoding="utf-8").read()

base_css = re.search(r'(<style>\n/\* Layout:[\s\S]*?</style>)', src).group(1)
header = re.search(r'(<header class="nav">[\s\S]*?</header>)', src).group(1)
footer = re.search(r'(<footer>[\s\S]*?</footer>)', src).group(1)
# header/footer already contain the Blog link once the existing pages are updated; ensure it here
if '/blog/' not in header:
    header = header.replace('<a href="/contact/" data-route="contact">Contact Us</a>',
        '<a href="/blog/" data-route="blog">Blog</a>\n      <a href="/contact/" data-route="contact">Contact Us</a>')
header = re.sub(r'<a href="/blog/"[^>]*>Blog</a>', '<a href="/blog/" aria-current="page">Blog</a>', header)
header = re.sub(r' data-route="\w+"', '', header)
if '/blog/' not in footer:
    footer = footer.replace('<a href="/services/">Services</a>', '<a href="/services/">Services</a><a href="/blog/">Blog</a>')

BLOG_CSS = """<style>
/* BLOG */
.blog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
.post-card { text-decoration: none; transition: border-color .2s, transform .2s; }
.post-card:hover { border-color: color-mix(in oklab, var(--ember) 55%, var(--line)); transform: translateY(-2px); }
.post-card .tag, .post-meta .tag { font-family: var(--mono); font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: var(--ember); }
.post-card h2 { font-size: 1.15rem; font-weight: 600; line-height: 1.3; }
.post-card .more { color: var(--cyan); font-weight: 600; font-size: 14px; }
.post-meta { display: flex; flex-wrap: wrap; gap: 8px 18px; align-items: center; color: var(--muted); font-size: 14px; }
.crumbs { font-size: 14px; color: var(--muted); }
.crumbs a { color: var(--muted); text-decoration: none; } .crumbs a:hover { color: var(--fg); }
.article { max-width: 760px; font-size: 1.05rem; line-height: 1.75; }
.article > * + * { margin-top: 1.1em; }
.article h2 { font-size: clamp(1.35rem, 2.6vw, 1.7rem); font-weight: 600; margin-top: 2em; }
.article h3 { font-size: 1.12rem; font-weight: 600; margin-top: 1.6em; font-family: var(--body); font-weight: 700; }
.article p, .article li { color: #cfd3e3; }
.article strong { color: var(--fg); }
.article a { color: var(--cyan); text-underline-offset: 3px; }
.article ul, .article ol { padding-left: 1.3em; }
.article li + li { margin-top: .45em; }
.article .note { background: var(--surface); border: 1px solid var(--line); border-left: 3px solid var(--cyan); border-radius: 10px; padding: 14px 18px; color: var(--muted); font-size: .97rem; }
.article .note b { color: var(--fg); }
.article .formula { font-family: var(--mono); font-size: .98rem; background: var(--surface-2); border: 1px solid var(--line); border-radius: 10px; padding: 16px 18px; color: var(--ember-hot); overflow-x: auto; }
.table-wrap { overflow-x: auto; border: 1px solid var(--line); border-radius: 12px; }
.article table { width: 100%; border-collapse: collapse; font-size: .95rem; min-width: 520px; }
.article th, .article td { text-align: left; padding: 11px 14px; border-bottom: 1px solid var(--line); vertical-align: top; }
.article th { background: var(--surface-2); color: var(--fg); font-weight: 700; }
.article tr:last-child td { border-bottom: 0; }
.post-cta { margin-top: 48px; background: linear-gradient(160deg, var(--surface-2), var(--bg-2)); border: 1px solid var(--line); border-radius: 18px; padding: clamp(22px, 4vw, 34px); display: grid; gap: 14px; max-width: 760px; }
.post-cta h2 { font-size: 1.35rem; font-weight: 600; }
.post-cta p { color: var(--muted); }
.post-risk { max-width: 760px; margin-top: 28px; font-size: 13px; color: var(--muted); }
.related { margin-top: 64px; }
.related h2 { font-size: 1.3rem; font-weight: 600; margin-bottom: 20px; }
</style>"""

# Recommended-broker box. Shown on posts whose front matter has "brokers: yes".
# Edit links here only — they are XDemat's partner (IB) links, same as on /products/.
BROKERS_BOX = """<aside class="brokers" aria-labelledby="rb-title" style="margin-top:40px;max-width:760px">
        <div class="brokers-head">
          <span class="eyebrow">Our recommended brokers</span>
          <h3 id="rb-title">Open your trading account</h3>
          <p>These are the brokers we recommend to XRover 5 members. Open your account through these links to qualify for special XRover 5 pricing.</p>
        </div>
        <div class="broker-grid" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr))">
          <a class="broker" href="https://clickto.trade/bWvhiqPpr8h?ib=7377800" target="_blank" rel="noopener sponsored">
            <span class="blogo" style="background:#c4c0ad"><img src="/assets/broker-octa.png" alt="Octa" width="177" height="64" loading="lazy"></span>
            <span class="bname">Octa</span>
            <span class="bcta">Open account ↗</span>
          </a>
          <a class="broker" href="https://cabinet.switchmarkets.com/links/go/14777" target="_blank" rel="noopener sponsored">
            <span class="blogo" style="background:#000c2e"><img src="/assets/broker-switch.png" alt="Switch Markets" width="260" height="82" loading="lazy"></span>
            <span class="bname">Switch Markets</span>
            <span class="bcta">Open account ↗</span>
          </a>
        </div>
        <div class="btn-row"><a class="btn btn-ghost" href="/products/#brokers">How the special offer works</a><a class="btn btn-ghost" href="/contact/">Already opened? Contact us</a></div>
        <p class="broker-risk"><b>Disclosure:</b> these are partner links. XDemat may receive a commission from the broker when you open an account through them, at no extra cost to you. Broker accounts are provided by the brokers, not by XDemat. Check that trading with any broker is permitted under the rules that apply to you (for Indian residents, RBI/FEMA rules) and read the broker's terms before opening an account. Trading forex and CFDs carries a high risk of losing money.</p>
      </aside>"""

MENU_JS = """<script>
(function(){var b=document.getElementById('menuBtn'),l=document.getElementById('links');
b.addEventListener('click',function(){var o=l.classList.toggle('open');b.setAttribute('aria-expanded',String(o));});})();
</script>"""

def words(h): return len(re.sub(r'<[^>]+>', ' ', h).split())

def head(title, desc, url, ogtype, ld):
    t, d = html.escape(title), html.escape(desc)
    return f"""<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{t}</title>
<meta name="description" content="{d}">
<link rel="canonical" href="{url}">
<meta name="robots" content="index, follow, max-image-preview:large">
<meta name="theme-color" content="#0a0b12">
<meta property="og:type" content="{ogtype}">
<meta property="og:site_name" content="XRover 5">
<meta property="og:url" content="{url}">
<meta property="og:title" content="{t}">
<meta property="og:description" content="{d}">
<meta property="og:image" content="https://xrover5.com/assets/og-image.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{t}">
<meta name="twitter:description" content="{d}">
<meta name="twitter:image" content="https://xrover5.com/assets/og-image.jpg">
<link rel="icon" type="image/png" href="/assets/favicon.png">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
<script type="application/ld+json">
{json.dumps(ld, indent=1, ensure_ascii=False)}
</script>
<style>html{{color-scheme:dark}}body{{margin:0}}img{{max-width:100%}}[hidden]{{display:none!important}}:root{{padding-top:env(safe-area-inset-top,0px);padding-bottom:env(safe-area-inset-bottom,0px)}}</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Unbounded:wght@400;600;800&family=Manrope:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap">
{base_css}
{BLOG_CSS}
</head>
<body>
{header}
"""

def tail(): return f"\n{footer}\n\n{MENU_JS}\n</body>\n</html>\n"

def card(p):
    mins = max(3, round(words(p['body']) / 200))
    return f"""<a class="card post-card" href="/blog/{p['slug']}/">
        <span class="tag">{p['cat']}</span>
        <h2>{html.escape(p['title'])}</h2>
        <p>{html.escape(p['desc'])}</p>
        <span class="more"><time datetime="{p['date']}">{p['date_h']}</time> · Read article · {mins} min →</span>
      </a>"""

ORG = {"@type": "Organization", "name": "XDemat", "url": "https://xrover5.com/", "logo": "https://xrover5.com/assets/xdemat-logo.webp"}

# Posts
for i, p in enumerate(POSTS):
    url = f"https://xrover5.com/blog/{p['slug']}/"
    mins = max(3, round(words(p['body']) / 200))
    ld = {"@context": "https://schema.org", "@graph": [
        {"@type": "BlogPosting", "headline": p['title'], "description": p['desc'], "image": "https://xrover5.com/assets/og-image.jpg",
         "datePublished": p["date"], "dateModified": p["date"], "author": ORG, "publisher": ORG, "mainEntityOfPage": url, "inLanguage": "en"},
        {"@type": "BreadcrumbList", "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "Home", "item": "https://xrover5.com/"},
            {"@type": "ListItem", "position": 2, "name": "Blog", "item": "https://xrover5.com/blog/"},
            {"@type": "ListItem", "position": 3, "name": p['title'], "item": url}]}]}
    others = [q for q in POSTS if q is not p]
    related = ([q for q in others if q["cat"] == p["cat"]] + [q for q in others if q["cat"] != p["cat"]])[:3]
    page = head(f"{p['title']} | XRover 5 Blog", p['desc'], url, "article", ld) + f"""
<main>
<div class="page">
  <div class="wrap page-head">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Home</a> / <a href="/blog/">Blog</a> / {p['cat']}</nav>
    <h1 style="font-size:clamp(1.9rem,4.4vw,3.1rem);max-width:22ch">{html.escape(p['title'])}</h1>
    <div class="post-meta"><span class="tag">{p['cat']}</span><span>By XDemat</span><time datetime="{p['date']}">{p['date_h']}</time><span>{mins} min read</span></div>
  </div>
  <section class="band" style="padding-top:28px">
    <div class="wrap">
      <article class="article">
{p['body'].strip()}
      </article>
      {BROKERS_BOX if p["brokers"] else ""}
      <div class="post-cta">
        <span class="eyebrow">XRover 5 · Intelligent Candle Trading System</span>
        <h2>See how XRover 5 trades XAUUSD in real market conditions</h2>
        <p>Book a live demo for a full walkthrough of confirmed entries, adaptive zones, stop-loss and target management, and Telegram alerts, with time for your questions.</p>
        <div class="btn-row"><a class="btn btn-primary" href="/contact/">Book a Demo</a><a class="btn btn-ghost" href="/products/">Explore features</a></div>
      </div>
      <p class="post-risk">This article is for education only and is not financial advice. Trading gold and other CFDs with leverage carries a high risk of losing money. Prices and examples above are illustrative.</p>
      <div class="related">
        <h2>Keep reading</h2>
        <div class="blog-grid">
      {''.join(card(r) for r in related)}
        </div>
      </div>
    </div>
  </section>
</div>
</main>
""" + tail()
    os.makedirs(f"{ROOT}/blog/{p['slug']}", exist_ok=True)
    open(f"{ROOT}/blog/{p['slug']}/index.html", "w").write(page)

# Index
ld = {"@context": "https://schema.org", "@type": "Blog", "name": "XRover 5 Blog", "url": "https://xrover5.com/blog/", "publisher": ORG,
      "blogPost": [{"@type": "BlogPosting", "headline": p['title'], "url": f"https://xrover5.com/blog/{p['slug']}/", "datePublished": p["date"]} for p in POSTS]}
idx = head("XAUUSD Gold Trading Blog | XRover 5 by XDemat",
           "Free guides on trading XAUUSD (gold): sessions in IST, lot size and position sizing, what moves gold prices, candlestick reading and risk management.",
           "https://xrover5.com/blog/", "website", ld) + f"""
<main>
<div class="page">
  <div class="wrap page-head">
    <span class="eyebrow">Blog</span>
    <h1>XAUUSD trading insights</h1>
    <p class="lead">Practical, no-hype guides to trading gold: when to trade it from India, how to size positions, what moves the price, how to read candles and how to protect your capital.</p>
  </div>
  <section class="band" style="padding-top:36px">
    <div class="wrap">
      <div class="blog-grid">
      {''.join(card(p) for p in POSTS)}
      </div>
    </div>
  </section>
</div>
</main>
""" + tail()
open(f"{ROOT}/blog/index.html", "w").write(idx)

# Remove pages of posts that no longer exist
for d in glob.glob(f"{ROOT}/blog/*/"):
    if os.path.basename(d.rstrip("/")) not in {p["slug"] for p in POSTS}:
        import shutil; shutil.rmtree(d)

# Sitemap: keep non-blog entries, rewrite blog entries
sm = open(f"{ROOT}/sitemap.xml", encoding="utf-8").read()
sm = re.sub(r"\s*<url><loc>https://xrover5\.com/blog/.*?</url>", "", sm)
latest = POSTS[0]["date"] if POSTS else datetime.date.today().isoformat()
lines = [f"  <url><loc>https://xrover5.com/blog/</loc><lastmod>{latest}</lastmod><priority>0.8</priority></url>"]
lines += [f"  <url><loc>https://xrover5.com/blog/{p['slug']}/</loc><lastmod>{p['date']}</lastmod><priority>0.7</priority></url>" for p in POSTS]
sm = re.sub(r"\s*</urlset>", "\n" + "\n".join(lines) + "\n</urlset>", sm)
open(f"{ROOT}/sitemap.xml", "w").write(sm)
print("built", len(POSTS), "posts")
