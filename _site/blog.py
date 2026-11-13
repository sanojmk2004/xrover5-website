# Blog for the XRover Tech site. Executed by _site/build.py (shares its names: page, write, SITE, ORG, html, json, re, glob, datetime).
# Sources: _site/posts/*.html with a front-matter comment (see _site/BLOG.md).

BLOG_CSS = """<style>
.blog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr)); gap: 20px; }
.post-card { text-decoration: none; transition: border-color .2s, transform .2s; }
.post-card:hover { border-color: color-mix(in oklab, var(--cyan) 50%, var(--line)); transform: translateY(-2px); }
.post-card .tag, .post-meta .tag { font: 500 11px var(--mono); letter-spacing: .12em; text-transform: uppercase; color: var(--cyan); }
.post-card h2, .post-card h3 { font-size: 1.15rem; font-weight: 600; line-height: 1.3; font-family: var(--display); }
.post-card .more { color: var(--ember); font-weight: 700; font-size: 14px; }
.post-meta { display: flex; flex-wrap: wrap; gap: 8px 18px; align-items: center; color: var(--muted); font-size: 14px; }
.crumbs { font-size: 14px; color: var(--muted); } .crumbs a { color: var(--muted); text-decoration: none; } .crumbs a:hover { color: var(--fg); }
.article { max-width: 760px; font-size: 1.05rem; line-height: 1.75; }
.article > * + * { margin-top: 1.1em; }
.article h2 { font-size: clamp(1.35rem, 2.6vw, 1.7rem); font-weight: 600; margin-top: 2em; }
.article h3 { font: 700 1.12rem var(--body); margin-top: 1.6em; }
.article p, .article li { color: #cfd3e3; }
.article strong { color: var(--fg); }
.article a { color: var(--cyan); text-underline-offset: 3px; }
.article ul, .article ol { padding-left: 1.3em; }
.article li + li { margin-top: .45em; }
.article .note { background: var(--surface); border: 1px solid var(--line); border-left: 3px solid var(--cyan); border-radius: 10px; padding: 14px 18px; color: var(--muted); font-size: .97rem; }
.article .note b { color: var(--fg); }
.table-wrap { overflow-x: auto; border: 1px solid var(--line); border-radius: 12px; }
.article table { width: 100%; border-collapse: collapse; font-size: .95rem; min-width: 520px; }
.article th, .article td { text-align: left; padding: 11px 14px; border-bottom: 1px solid var(--line); vertical-align: top; }
.article th { background: var(--surface-2); color: var(--fg); }
.article tr:last-child td { border-bottom: 0; }
.article .faq h3 { margin-top: 1.3em; }
.post-cta { margin-top: 48px; background: linear-gradient(160deg, var(--surface-2), var(--bg-2)); border: 1px solid var(--line); border-radius: 18px; padding: clamp(22px, 4vw, 34px); display: grid; gap: 14px; max-width: 760px; }
.post-cta h2 { font-size: 1.35rem; font-weight: 600; } .post-cta p { color: var(--muted); }
.related { margin-top: 64px; } .related > h2 { font-size: 1.3rem; font-weight: 600; margin-bottom: 20px; }
</style>"""

# Product each post can point to (front matter "product:")
PRODUCT_CTA = {
  "crm":       ("XRover CRM", "/products/#crm", "See how XRover CRM runs leads, WhatsApp, HR and accounts in one place.", "Book a CRM demo"),
  "adclearx":  ("AdClearX", "/products/#adclearx", "Check your Meta ads against policy before you publish, with clear suggestions to improve them.", "Request early access"),
  "connector": ("TradingView → MT5 Connector", "/products/#mt5-connector", "Webhook automation between TradingView and MetaTrader 5, with a licensed MT5 add-on.", "Join the waiting list"),
  "xrover5":   ("XRover 5", "/products/#xrover5", "Chart-analysis software for TradingView with adaptive zones, an on-chart dashboard and Telegram alerts.", "Book a demo"),
  "":          ("XRover Tech", "/services/", "Have a product idea or a process to automate? We design, build and support custom software.", "Start a project"),
}

def _front(f):
    raw = open(f, encoding="utf-8").read()
    m = re.match(r"\s*<!--(.*?)-->(.*)", raw, re.S)
    if not m: sys.exit(f"{f}: missing <!-- front matter -->")
    meta = dict(re.findall(r"^\s*(\w+):\s*(.+?)\s*$", m.group(1), re.M))
    for k in ("slug", "title", "description", "category", "date"):
        if k not in meta: sys.exit(f"{f}: missing {k}")
    if not re.fullmatch(r"[a-z0-9-]+", meta["slug"]): sys.exit(f"{f}: bad slug")
    if meta.get("product", "") not in PRODUCT_CTA: sys.exit(f"{f}: unknown product {meta.get('product')}")
    d = datetime.date.fromisoformat(meta["date"])
    return {"slug": meta["slug"], "title": meta["title"], "desc": meta["description"], "cat": meta["category"],
            "product": meta.get("product", ""), "d": d, "date": d.isoformat(), "date_h": f"{d.day} {d.strftime('%B %Y')}",
            "body": m.group(2).strip(), "path": f"/blog/{meta['slug']}/"}

POSTS = [_front(f) for f in sorted(glob.glob(os.path.join(HERE, "posts", "*.html")))]
_slugs = [p["slug"] for p in POSTS]
if len(_slugs) != len(set(_slugs)): sys.exit("duplicate slug in _site/posts")
POSTS.sort(key=lambda p: (p["date"], p["slug"]), reverse=True)
TODAY = datetime.date.today()
POSTS = [p for p in POSTS if p["d"] <= TODAY]     # future-dated posts wait until their date

def _mins(b): return max(3, round(len(re.sub(r"<[^>]+>", " ", b).split()) / 200))

def post_card(p, h="h2"):
    return f"""<a class="card post-card" href="{p['path']}">
        <span class="tag">{html.escape(p['cat'])}</span>
        <{h}>{html.escape(p['title'])}</{h}>
        <p>{html.escape(p['desc'])}</p>
        <span class="more"><time datetime="{p['date']}">{p['date_h']}</time> · {_mins(p['body'])} min read →</span>
      </a>"""

def _faq(body):
    m = re.search(r'<div class="faq">([\s\S]*?)</div>', body)
    if not m: return []
    qa = re.findall(r"<h3>([\s\S]*?)</h3>\s*<p>([\s\S]*?)</p>", m.group(1))
    clean = lambda s: html.unescape(re.sub(r"<[^>]+>", "", s)).strip()
    return [{"@type": "Question", "name": clean(q), "acceptedAnswer": {"@type": "Answer", "text": clean(a)}} for q, a in qa]

for p in POSTS:
    url = SITE + p["path"]
    name, href, line, btn = PRODUCT_CTA[p["product"]]
    others = [q for q in POSTS if q is not p]
    related = ([q for q in others if q["product"] == p["product"]] + [q for q in others if q["product"] != p["product"]])[:3]
    ld = [{"@type": "BlogPosting", "headline": p["title"], "description": p["desc"], "image": f"{SITE}/assets/og-xrover-tech.jpg",
           "datePublished": p["date"], "dateModified": p["date"], "author": {"@id": f"{SITE}/#org"}, "publisher": {"@id": f"{SITE}/#org"},
           "mainEntityOfPage": url, "inLanguage": "en"},
          {"@type": "BreadcrumbList", "itemListElement": [
              {"@type": "ListItem", "position": 1, "name": "Home", "item": SITE + "/"},
              {"@type": "ListItem", "position": 2, "name": "Blog", "item": SITE + "/blog/"},
              {"@type": "ListItem", "position": 3, "name": p["title"], "item": url}]}]
    faq = _faq(p["body"])
    if faq: ld.append({"@type": "FAQPage", "mainEntity": faq})
    body = f"""  <div class="wrap page-head">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Home</a> / <a href="/blog/">Blog</a> / {html.escape(p['cat'])}</nav>
    <h1 style="font-size:clamp(1.9rem,4.4vw,3.1rem);max-width:24ch">{html.escape(p['title'])}</h1>
    <div class="post-meta"><span class="tag">{html.escape(p['cat'])}</span><span>By XRover Tech</span><time datetime="{p['date']}">{p['date_h']}</time><span>{_mins(p['body'])} min read</span></div>
  </div>
  <section class="band" style="padding-top:28px">
    <div class="wrap">
      <article class="article">
{p['body']}
      </article>
      <aside class="post-cta">
        <span class="eyebrow">{html.escape(name)}</span>
        <h2>{html.escape(line)}</h2>
        <div class="btn-row">{wa_btn(btn, name)}<a class="btn btn-ghost" href="{href}">Learn more</a></div>
      </aside>
      {f'<div class="related"><h2>Keep reading</h2><div class="blog-grid">{"".join(post_card(r, "h3") for r in related)}</div></div>' if related else ''}
    </div>
  </section>"""
    write(p["path"].strip("/") + "/index.html",
          page(p["path"], f"{p['title']} | XRover Tech Blog", p["desc"], body, ld, ogtype="article", head_extra=BLOG_CSS, webpage=False))

# Blog index
_grid = "".join(post_card(p) for p in POSTS) or '<p class="lead">New articles are coming soon.</p>'
blog_body = f"""  <div class="wrap page-head">
    <span class="eyebrow">Blog</span>
    <h1>Notes from XRover Tech</h1>
    <p class="lead">Product guides and practical articles on CRM and WhatsApp automation, Meta ad compliance, TradingView tools and building business software.</p>
  </div>
  <section class="band" style="padding-top:36px">
    <div class="wrap"><div class="blog-grid">
      {_grid}
    </div></div>
  </section>"""
write("blog/index.html", page("/blog/", "Blog – CRM, WhatsApp Automation, Meta Ads & TradingView Software | XRover Tech",
      "Guides from XRover Tech on CRM and WhatsApp Business API automation, Meta ad policy checks, TradingView tools and custom business software.",
      blog_body, [{"@type": "Blog", "name": "XRover Tech Blog", "url": SITE + "/blog/", "publisher": {"@id": f"{SITE}/#org"},
                   "blogPost": [{"@type": "BlogPosting", "headline": p["title"], "url": SITE + p["path"], "datePublished": p["date"]} for p in POSTS]}],
      head_extra=BLOG_CSS))

# Remove generated pages of posts that no longer exist
_live = {p["slug"] for p in POSTS}
import shutil
for d in glob.glob(os.path.join(ROOT, "blog", "*", "")):
    if os.path.basename(d.rstrip("/")) not in _live: shutil.rmtree(d)

# Homepage section (inserted by build.py)
LATEST_SECTION = (f"""  <section class="band">
    <div class="wrap">
      <div class="band-head"><span class="eyebrow">From the blog</span><h2>Latest articles</h2></div>
      {BLOG_CSS}
      <div class="blog-grid">
      {''.join(post_card(p, 'h3') for p in POSTS[:3])}
      </div>
      <div class="btn-row" style="margin-top:28px"><a class="btn btn-ghost" href="/blog/">All articles →</a></div>
    </div>
  </section>
""" if POSTS else "")

# Sitemap
_pages = [("/", "1.0"), ("/products/", "0.9"), ("/services/", "0.8"), ("/blog/", "0.8"), ("/contact/", "0.7")]
_today = TODAY.isoformat()
SITEMAP = '<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' + \
    "".join(f"  <url><loc>{SITE}{u}</loc><lastmod>{(POSTS[0]['date'] if u in ('/', '/blog/') and POSTS else _today)}</lastmod><priority>{pr}</priority></url>\n" for u, pr in _pages) + \
    "".join(f"  <url><loc>{SITE}{p['path']}</loc><lastmod>{p['date']}</lastmod><priority>0.7</priority></url>\n" for p in POSTS) + "</urlset>\n"
