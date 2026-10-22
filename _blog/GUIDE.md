# XRover 5 blog — how to add a post

The blog at https://xrover5.com/blog/ is generated from the files in `_blog/posts/`.
`_blog/` itself is never copied to the live site (see `deploy.sh`).

## Adding a post
1. Create `_blog/posts/YYYY-MM-DD-<slug>.html`:
   ```
   <!--
   slug: lowercase-words-with-hyphens
   category: one of the categories below
   title: Article title
   description: 140–160 character summary for Google and the blog card
   date: YYYY-MM-DD
   -->
   <p>Intro…</p>
   <h2>…</h2>
   ```
   The body is plain HTML: `<p> <h2> <h3> <ul>/<ol> <li> <strong> <em> <a>`, plus
   `<div class="note"><b>Label:</b> text</div>`, `<div class="formula">…</div>` and tables wrapped in
   `<div class="table-wrap"><table>…</table></div>`. No `<h1>` (the title becomes the h1), no images, no scripts, no inline styles.
2. Run `python3 _blog/build.py`. It rebuilds `blog/`, the blog index and the blog entries in `sitemap.xml`,
   and stops with an error if the front matter is incomplete or a slug is duplicated.
3. Commit and push to `main`. The cPanel cron copies it live.

Do not edit files in `blog/` by hand — they are overwritten on every build.

## Categories
Beginner Guide · Trading Sessions · Risk & Money · Fundamentals · Technical Analysis · Strategy · Trading Psychology · Market Update

## Writing rules
- Audience: retail traders in India (many trade in the evening IST), beginner to intermediate. Topic focus: XAUUSD (gold).
- 900–1,400 words, clear English, short paragraphs, practical and specific. Use IST when giving times.
- Educational only. Never promise or imply profits, win rates or "guaranteed" results. No "get rich" language.
- No broker names or broker links in articles.
- No specific current gold price or recent news unless you have looked it up that day and say when it applies.
  Example prices must be clearly illustrative ("for example, at 4,000.00").
- Facts (session times, contract sizes, release times, how drivers work) must be correct. If unsure, leave it out.
- Link to 1–3 earlier posts where relevant, using `/blog/<slug>/`.
- End with one short paragraph connecting the topic to genuine XRover 5 features (Data Confirmed Entry System,
  Adaptive Zone Technology, Intelligent Stop Loss Zones, Dynamic Profit Targeting TP1/TP2/TP3, Beginner Safe Exit Mode,
  Professional Trader Mode, News Data Awareness, Trade Opportunity Countdown, Telegram notifications,
  built-in strategy library, Fear Exit Mode). Do not invent features or performance numbers.
  The page template already adds a "Book a Demo" box and a risk note — don't repeat them.
- Original writing only — never copy text from other websites.

## Topic backlog
Pick the first unused topic, or a new XAUUSD topic not already covered by a file in `posts/`.
Delete a line from this list when you publish it.
- XAUUSD scalping vs intraday vs swing trading: which style suits you
- How to trade gold around Non-Farm Payrolls (and when not to)
- Support and resistance on XAUUSD: drawing zones that matter
- Gold and the US Dollar Index (DXY): reading them together
- Multi-timeframe analysis for gold traders
- Trading psychology: handling losing streaks on XAUUSD
- XAUUSD spreads, swaps and overnight fees explained
- Breakout vs fakeout: trading the London open on gold
- Why gold respects round numbers
- How to build an XAUUSD trading plan (with a template)
- Using a demo account properly before trading gold live
- Gold vs silver (XAUUSD vs XAGUSD) for traders
- How Fed rate decisions affect gold, step by step
- The Asian session range strategy for XAUUSD
- Trailing stops and partial profits on gold
- Common mistakes new gold traders make
- How to keep a trading journal for XAUUSD
- Reading the economic calendar as a gold trader
- Gold's seasonal patterns: myth or useful?
- Trend-following on XAUUSD with moving averages
- Why central banks buy gold and what it means for traders
- Overtrading: signs, causes and how to stop
- What is slippage and how to reduce it on gold
- Risk-reward ratio explained with XAUUSD examples
