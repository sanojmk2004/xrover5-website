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
   rebate: yes         (optional — adds the IB Rebate Program box linking to /rebate/)
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

Do not edit files in `blog/`, `ml/`, `ta/` or `hi/` by hand — they are overwritten on every build.

## Translations: Malayalam (ml), Tamil (ta), Hindi (hi)
Every English article is also published in Malayalam, Tamil and Hindi at `/ml/blog/<slug>/`, `/ta/blog/<slug>/`, `/hi/blog/<slug>/`.
- File: `_blog/posts/<lang>/<same file name as the English post>.html`, front matter:
  ```
  <!--
  slug: <exactly the same slug as the English post>
  title: <title in that language>
  description: <140–160 character summary in that language>
  -->
  ```
  Category, date and the `brokers`/`rebate` boxes come from the English post automatically (the boxes are translated by the template).
- Translate the meaning naturally, the way traders in Kerala, Tamil Nadu and Hindi-speaking India actually talk and search —
  not word-for-word. Everyday trading words that people say in English are written in the local script as they are said
  (e.g. ട്രേഡിംഗ്, ലോട്ട്, സ്റ്റോപ്പ്-ലോസ് / டிரேடிங், லாட், ஸ்டாப்-லாஸ் / ट्रेडिंग, लॉट, स्टॉप-लॉस).
  Keep symbols, product and feature names in English letters: XAUUSD, DXY, CPI, NFP, MT4/MT5, USDT, TRC20, CFD, IB,
  XRover 5, XDemat, Beginner Safe Exit Mode, Fear Exit Mode, etc.
- Times: IST first, GMT in brackets, e.g. "19:00 IST (13:30 GMT)".
- Title and description: use the phrases people type in that language, e.g. "ഗോൾഡ് ട്രേഡിംഗ് മലയാളം",
  "தங்கம் டிரேடிங் தமிழ்", "गोल्ड ट्रेडिंग हिन्दी में".
- Keep exactly the same HTML structure as the English post. Leave links as `/blog/<slug>/` — the build points them to the
  same-language version when it exists.
- All writing rules above apply unchanged (no profit promises, no broker names in text, accurate facts, illustrative prices).
- `python3 _blog/build.py` lists English posts that are not yet translated in each language.

## Categories
Beginner Guide · Trading Sessions · Risk & Money · Fundamentals · Technical Analysis · Strategy · Trading Psychology · Market Update · Brokers & Accounts · XRover 5 · IB Rebates

## Writing rules
- Audience: retail traders worldwide — especially India, the Middle East, South-East Asia and Africa — beginner to intermediate.
  Topic focus: XAUUSD (gold). Write in simple international English (many readers are not native speakers): short sentences,
  no idioms or slang, explain every term once.
- 900–1,400 words, short paragraphs, practical and specific. Give times in GMT first with IST in brackets,
  e.g. "13:30 GMT (19:00 IST)", and note daylight-saving changes where relevant. Use USD for money examples.
- Write titles and descriptions people actually search for worldwide (e.g. "forex rebate", "forex cashback",
  "how to choose a forex broker"), with the main phrase early in the title.
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
- Readers are worldwide: explain how to check a broker's licence with the reader's own regulator and the regulator's
  public register (examples: FCA UK, ASIC Australia, CySEC Cyprus, FSCA South Africa) without claiming any broker holds a particular licence.
  Include one short paragraph for Indian readers: Indian residents must follow RBI/FEMA rules on forex trading, RBI publishes an
  "Alert List" of unauthorised forex platforms, and they should check current rules and the list themselves.
  Do not give legal advice or say any broker is "approved" or "legal" anywhere.
- Set `brokers: yes` in the front matter so the template adds the Recommended brokers box (Octa and Switch Markets,
  partner links, with disclosure). Never type broker links yourself — the box is the only source of the correct links.
- The article text stays neutral and educational. It may end with one sentence pointing readers to
  "our recommended brokers below" and the XRover 5 special pricing. Don't make claims about the brokers
  (regulation, spreads, bonuses, safety) beyond that, and don't rank or review them.
- Use `brokers: yes` only on broker and account-opening articles.

### IB rebate articles (category "IB Rebates")
- Set `rebate: yes` (adds the IB Rebate Program box). Add `brokers: yes` too on articles about opening an account for rebates.
- How the program works (from https://xrover5.com/rebate/): trader opens an MT4/MT5 account with a partner broker through
  XDemat's IB link → connects it with the read-only investor password → XDemat sees traded lots → when the broker pays XDemat's
  IB commission, the trader's share goes to their balance → payout in USDT (TRC20 or BEP20) to the wallet they add, on request
  above a minimum. Free to join; the trader's spreads and commissions are the broker's normal ones. Only accounts opened through
  XDemat's link qualify. Investor access can view trades only; it cannot trade or withdraw.
- Never state a specific rebate percentage, $-per-lot amount, minimum payout or payout time — they are set by the admin and can change.
  Worked examples must say they are illustrative ("for example, if a rebate were $X per lot…").
- Never encourage trading more, bigger or more often to earn rebates. Say plainly that a rebate lowers trading costs but does not
  make a losing strategy profitable, and that trading risk is unchanged.
- Remind readers to check that crypto payouts are allowed where they live and that rebates may be taxable.
- Explaining what an IB is and how rebates/cashback work in general is fine; don't name or criticise other rebate providers.

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
- How to open a forex trading account step by step: KYC, documents, demo, first deposit (category: Brokers & Accounts)
- What is a forex rebate (IB cashback) and how does it work? (category: IB Rebates)
- What makes XRover 5 the Intelligent Candle Trading System — and how it differs from ordinary candle tools (category: XRover 5)
- Why professional traders use XRover 5: confirmation, structure and control (category: XRover 5)
- What is an introducing broker (IB)? How IB commissions are paid and shared (category: IB Rebates)
- XRover 5 vs traditional candlestick systems, indicators and order flow: an honest comparison (category: XRover 5)
- How forex rebates lower your real trading cost on XAUUSD: worked examples (category: IB Rebates)
- Is it safe to share your MT4/MT5 investor password? Read-only access explained (category: IB Rebates)
- How to get forex rebates paid in USDT: wallets, TRC20 vs BEP20 and avoiding mistakes (category: IB Rebates)
- Forex rebate step by step: open an account through XDemat, connect MT5 and request your first payout (category: IB Rebates)
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
