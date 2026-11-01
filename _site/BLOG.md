# XRover Tech blog

Posts live in `_site/posts/YYYY-MM-DD-<slug>.html`. Build the whole site (pages, blog, sitemap) with:

    python3 _site/build.py

Then commit and push to `main`; the cPanel cron copies it live. Files whose names start with `_` are never published.
Posts dated in the future are skipped until that date (re-run the build on or after it).

## Front matter
```
<!--
slug: lowercase-words-with-hyphens
title: Article title (main search phrase early)
description: 140–160 character summary for Google and the blog card
category: Product Guide | WhatsApp Automation | Meta Ads | Automation | Software Development | Company
product: crm | adclearx | connector | xrover5 | (empty for general posts)
date: YYYY-MM-DD
-->
```
`product` picks the call-to-action box at the end of the post.

## Body
Plain HTML: `<p> <h2> <h3> <ul>/<ol> <li> <strong> <em> <a>`, `<div class="note"><b>Label:</b> text</div>`,
tables in `<div class="table-wrap"><table>…</table></div>`. No `<h1>`, images, scripts or inline styles.
End with an FAQ block (turned into FAQPage structured data):
```
<h2>Frequently asked questions</h2>
<div class="faq">
<h3>Question as people search it?</h3>
<p>Answer in 1–3 sentences, plain text.</p>
</div>
```

## Rules
- XRover Tech is a software company. Write about software: CRM, WhatsApp Business API, automation, Meta ads, TradingView tools, custom development.
- No financial-services content: no brokers, investment/trading advice, profit or return claims, rebates, or XDemat. XRover 5 and the
  MT5 Connector are described as analysis/automation software only, with the "software, not advice" note.
- Only describe features that exist (see /products/). No invented numbers, testimonials or guarantees.
- Original writing, simple international English, 800–1,300 words.
