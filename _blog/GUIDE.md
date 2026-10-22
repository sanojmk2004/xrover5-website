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
   brokers: yes        (optional — adds the Recommended brokers box with XDemat's partner links)
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
Beginner Guide · Trading Sessions · Risk & Money · Fundamentals · Technical Analysis · Strategy · Trading Psychology · Market Update · Brokers & Accounts · XRover 5

## Writing rules
- Audience: retail traders in India (many trade in the evening IST), beginner to intermediate. Topic focus: XAUUSD (gold).
- 900–1,400 words, clear English, short paragraphs, practical and specific. Use IST when giving times.
- Educational only. Never promise or imply profits, win rates or "guaranteed" results. No "get rich" language.
- No broker names or broker links in the article text. Broker links appear only through the `brokers: yes` box (see below).
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

### Broker and account-opening articles
- Stay general and educational: regulation, safety of funds, costs, platforms, execution, deposits/withdrawals, support.
- Readers are mostly Indian residents. Explain that Indian residents must follow RBI/FEMA rules on forex trading,
  that RBI publishes an "Alert List" of unauthorised forex platforms, and that readers should check current rules
  and the list themselves before opening an account. Do not give legal advice or say any broker is "approved" or "legal in India".
- Set `brokers: yes` in the front matter so the template adds the Recommended brokers box (Octa and Switch Markets,
  partner links, with disclosure). Never type broker links yourself — the box is the only source of the correct links.
- The article text stays neutral and educational. It may end with one sentence pointing readers to
  "our recommended brokers below" and the XRover 5 special pricing. Don't make claims about the brokers
  (regulation, spreads, bonuses, safety) beyond that, and don't rank or review them.
- Use `brokers: yes` only on broker and account-opening articles.

### XRover 5 articles (category "XRover 5")
- These can be about XRover 5 throughout, but every claim must match the real features listed above
  and the products page (https://xrover5.com/products/). No invented features, statistics, win rates or testimonials.
- "Intelligent Candle Trading System" is XDemat's name for the product — use it freely. Do not state as fact
  that XRover 5 is the "world's only" or "world's best" system or that it outperforms other tools; explain what is
  distinctive about its approach instead, and let readers judge.
- Comparisons with other methods (plain candlestick patterns, standard indicators, order flow, volume profile, etc.)
  must be fair: describe what each method does well and its limits, then where XRover 5's approach differs.
  Never disparage named competitor products.
- End with the normal Book a Demo box (added by the template). Keep the risk-aware, no-guarantee tone.

## Topic backlog
Pick the first unused topic, or a new XAUUSD topic not already covered by a file in `posts/`.
Delete a line from this list when you publish it.
- How to choose a forex broker for trading gold: a safety checklist (category: Brokers & Accounts)
- How to open a forex trading account step by step: KYC, documents, demo, first deposit (category: Brokers & Accounts)
- What makes XRover 5 the Intelligent Candle Trading System — and how it differs from ordinary candle tools (category: XRover 5)
- Why professional traders use XRover 5: confirmation, structure and control (category: XRover 5)
- XRover 5 vs traditional candlestick systems, indicators and order flow: an honest comparison (category: XRover 5)
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
