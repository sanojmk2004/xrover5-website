"""Builds the multilingual blog.

Sources
  _blog/posts/*.html          English articles (the originals)
  _blog/posts/<lang>/*.html   Translations, lang = ml | ta | hi. Same file name and same slug as the English original.

Run from anywhere:  python3 _blog/build.py

Output
  /blog/ and /blog/<slug>/                 English
  /<lang>/blog/ and /<lang>/blog/<slug>/   Malayalam, Tamil, Hindi
  plus the blog entries in sitemap.xml and hreflang links between language versions.
Uses the stylesheet, header and footer of contact/index.html so the blog matches the site."""
import os, re, sys, json, html, glob, datetime, shutil

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SITE = "https://xrover5.com"
REQUIRED = ("slug", "category", "title", "description", "date")
REQUIRED_TR = ("slug", "title", "description")
FLAG = lambda v: str(v).lower() in ("yes", "true", "1")

# ---------------------------------------------------------------- language strings
EN_MONTHS = "January February March April May June July August September October November December".split()
L = {
 "en": dict(name="English", months=EN_MONTHS, font=None, base="/blog/",
   blog="Blog", home="Home", by="By XDemat", min_read="{n} min read", read="Read article", keep="Keep reading",
   idx_title="XAUUSD Gold Trading Blog | XRover 5 by XDemat",
   idx_desc="Free guides on trading XAUUSD (gold): trading sessions, lot size and position sizing, what moves gold prices, candlesticks, brokers, IB rebates and risk management.",
   idx_h1="XAUUSD trading insights",
   idx_lead="Practical, no-hype guides to trading gold: when to trade it, how to size positions, what moves the price, how to read candles and how to protect your capital.",
   soon="New articles are coming soon.",
   cta_h2="See how XRover 5 trades XAUUSD in real market conditions",
   cta_p="Book a live demo for a full walkthrough of confirmed entries, adaptive zones, stop-loss and target management, and Telegram alerts, with time for your questions.",
   demo="Book a Demo", features="Explore features",
   risk="This article is for education only and is not financial advice. Trading gold and other CFDs with leverage carries a high risk of losing money. Prices and examples above are illustrative.",
   b_eye="Our recommended brokers", b_h="Open your trading account",
   b_p='These are the brokers we recommend to XRover 5 members. Open your account through these links to qualify for special XRover 5 pricing and <a href="/rebate/" style="color:var(--cyan)">XDemat IB rebates</a>.',
   b_open="Open account ↗", b_offer="How the special offer works", b_contact="Already opened? Contact us",
   b_disc="<b>Disclosure:</b> these are partner links. XDemat may receive a commission from the broker when you open an account through them, at no extra cost to you. Broker accounts are provided by the brokers, not by XDemat. Check that trading with any broker is permitted under the rules that apply to you (for Indian residents, RBI/FEMA rules) and read the broker's terms before opening an account. Trading forex and CFDs carries a high risk of losing money.",
   r_eye="XDemat IB Rebate Program", r_h="Get part of your trading commission back in USDT",
   r_p="Open your MT4 or MT5 account with a partner broker through XDemat, connect it read-only, and receive a share of the IB commission on every lot you trade, paid to your crypto wallet. No extra cost to you.",
   r_reg="Create free rebate account", r_how="How it works",
   r_small="Rebates depend on the commission the broker actually pays and do not reduce trading risk. Check that the broker, crypto payouts and any tax on rebates are permitted where you live.",
   cats={}),
 "ml": dict(name="മലയാളം", font="Noto Sans Malayalam", base="/ml/blog/",
   months="ജനുവരി ഫെബ്രുവരി മാർച്ച് ഏപ്രിൽ മേയ് ജൂൺ ജൂലൈ ഓഗസ്റ്റ് സെപ്റ്റംബർ ഒക്ടോബർ നവംബർ ഡിസംബർ".split(),
   blog="ബ്ലോഗ്", home="ഹോം", by="എഴുതിയത്: XDemat", min_read="{n} മിനിറ്റ് വായന", read="ലേഖനം വായിക്കുക", keep="കൂടുതൽ വായിക്കാൻ",
   idx_title="XAUUSD ഗോൾഡ് ട്രേഡിംഗ് ബ്ലോഗ് മലയാളത്തിൽ | XRover 5",
   idx_desc="XAUUSD (ഗോൾഡ്) ട്രേഡിംഗ് മലയാളത്തിൽ പഠിക്കാം: ട്രേഡിംഗ് സമയം, ലോട്ട് സൈസ്, റിസ്ക് മാനേജ്മെന്റ്, കാൻഡിൽസ്റ്റിക്ക്, ബ്രോക്കർ, IB റിബേറ്റ് എന്നിവയെക്കുറിച്ചുള്ള ലളിതമായ ഗൈഡുകൾ.",
   idx_h1="XAUUSD ട്രേഡിംഗ് മലയാളത്തിൽ",
   idx_lead="ഗോൾഡ് ട്രേഡിംഗിനെക്കുറിച്ചുള്ള പ്രായോഗികവും സത്യസന്ധവുമായ ഗൈഡുകൾ: എപ്പോൾ ട്രേഡ് ചെയ്യണം, പൊസിഷൻ സൈസ് എങ്ങനെ കണക്കാക്കാം, വില മാറുന്നത് എന്തുകൊണ്ട്, കാൻഡിലുകൾ എങ്ങനെ വായിക്കാം, നിങ്ങളുടെ മൂലധനം എങ്ങനെ സംരക്ഷിക്കാം.",
   soon="പുതിയ ലേഖനങ്ങൾ ഉടൻ വരുന്നു.",
   cta_h2="യഥാർത്ഥ മാർക്കറ്റിൽ XRover 5 എങ്ങനെ XAUUSD ട്രേഡ് ചെയ്യുന്നു എന്ന് കാണൂ",
   cta_p="കൺഫേം ചെയ്ത എൻട്രികൾ, അഡാപ്റ്റീവ് സോണുകൾ, സ്റ്റോപ്പ്-ലോസ്, ടാർഗറ്റ് മാനേജ്മെന്റ്, ടെലിഗ്രാം അലേർട്ടുകൾ എന്നിവയുടെ പൂർണ്ണമായ വിശദീകരണത്തിനും നിങ്ങളുടെ സംശയങ്ങൾക്കുള്ള മറുപടിക്കും ഒരു ലൈവ് ഡെമോ ബുക്ക് ചെയ്യൂ.",
   demo="ഡെമോ ബുക്ക് ചെയ്യൂ", features="ഫീച്ചറുകൾ കാണൂ",
   risk="ഈ ലേഖനം വിദ്യാഭ്യാസ ആവശ്യത്തിന് മാത്രമുള്ളതാണ്, സാമ്പത്തിക ഉപദേശമല്ല. ലിവറേജ് ഉപയോഗിച്ച് ഗോൾഡും മറ്റ് CFD-കളും ട്രേഡ് ചെയ്യുമ്പോൾ പണം നഷ്ടപ്പെടാനുള്ള ഉയർന്ന സാധ്യതയുണ്ട്. മുകളിലെ വിലകളും ഉദാഹരണങ്ങളും വിശദീകരണത്തിന് മാത്രമുള്ളതാണ്.",
   b_eye="ഞങ്ങൾ ശുപാർശ ചെയ്യുന്ന ബ്രോക്കർമാർ", b_h="നിങ്ങളുടെ ട്രേഡിംഗ് അക്കൗണ്ട് തുറക്കൂ",
   b_p='XRover 5 അംഗങ്ങൾക്ക് ഞങ്ങൾ ശുപാർശ ചെയ്യുന്ന ബ്രോക്കർമാരാണ് ഇവ. ഈ ലിങ്കുകൾ വഴി അക്കൗണ്ട് തുറന്നാൽ XRover 5 സ്പെഷ്യൽ പ്രൈസിംഗിനും <a href="/rebate/" style="color:var(--cyan)">XDemat IB റിബേറ്റിനും</a> അർഹത നേടാം.',
   b_open="അക്കൗണ്ട് തുറക്കൂ ↗", b_offer="സ്പെഷ്യൽ ഓഫർ എങ്ങനെ ലഭിക്കും", b_contact="അക്കൗണ്ട് തുറന്നോ? ഞങ്ങളെ ബന്ധപ്പെടൂ",
   b_disc="<b>വെളിപ്പെടുത്തൽ:</b> ഇവ പാർട്ണർ ലിങ്കുകളാണ്. ഈ ലിങ്കുകൾ വഴി നിങ്ങൾ അക്കൗണ്ട് തുറന്നാൽ ബ്രോക്കറിൽ നിന്ന് XDemat-ന് കമ്മീഷൻ ലഭിച്ചേക്കാം; ഇതിന് നിങ്ങൾക്ക് അധിക ചെലവില്ല. ബ്രോക്കർ അക്കൗണ്ടുകൾ നൽകുന്നത് ബ്രോക്കർമാരാണ്, XDemat അല്ല. നിങ്ങൾക്ക് ബാധകമായ നിയമങ്ങൾ പ്രകാരം (ഇന്ത്യൻ റെസിഡന്റുകൾക്ക് RBI/FEMA നിയമങ്ങൾ) ഒരു ബ്രോക്കറുമായി ട്രേഡ് ചെയ്യാൻ അനുവാദമുണ്ടോ എന്ന് പരിശോധിക്കുക, അക്കൗണ്ട് തുറക്കുന്നതിന് മുമ്പ് ബ്രോക്കറുടെ നിബന്ധനകൾ വായിക്കുക. ഫോറെക്സ്, CFD ട്രേഡിംഗിൽ പണം നഷ്ടപ്പെടാനുള്ള ഉയർന്ന സാധ്യതയുണ്ട്.",
   r_eye="XDemat IB റിബേറ്റ് പ്രോഗ്രാം", r_h="നിങ്ങളുടെ ട്രേഡിംഗ് കമ്മീഷന്റെ ഒരു ഭാഗം USDT-യായി തിരികെ നേടൂ",
   r_p="XDemat വഴി ഒരു പാർട്ണർ ബ്രോക്കറിൽ MT4 അല്ലെങ്കിൽ MT5 അക്കൗണ്ട് തുറന്ന് റീഡ്-ഒൺലി ആയി കണക്റ്റ് ചെയ്യൂ. നിങ്ങൾ ട്രേഡ് ചെയ്യുന്ന ഓരോ ലോട്ടിലെയും IB കമ്മീഷന്റെ ഒരു വിഹിതം നിങ്ങളുടെ ക്രിപ്റ്റോ വാലറ്റിലേക്ക് ലഭിക്കും. നിങ്ങൾക്ക് അധിക ചെലവില്ല.",
   r_reg="സൗജന്യ റിബേറ്റ് അക്കൗണ്ട് തുടങ്ങൂ", r_how="ഇത് എങ്ങനെ പ്രവർത്തിക്കുന്നു",
   r_small="ബ്രോക്കർ യഥാർത്ഥത്തിൽ നൽകുന്ന കമ്മീഷനെ ആശ്രയിച്ചാണ് റിബേറ്റ്; ഇത് ട്രേഡിംഗ് റിസ്ക് കുറയ്ക്കുന്നില്ല. ബ്രോക്കറും ക്രിപ്റ്റോ പേഔട്ടുകളും നിങ്ങളുടെ രാജ്യത്ത് അനുവദനീയമാണോ എന്നും റിബേറ്റിന് നികുതി ബാധകമാണോ എന്നും പരിശോധിക്കുക.",
   cats={"Beginner Guide": "തുടക്കക്കാർക്ക്", "Trading Sessions": "ട്രേഡിംഗ് സെഷനുകൾ", "Risk & Money": "റിസ്ക് & മണി",
         "Fundamentals": "ഫണ്ടമെന്റൽസ്", "Technical Analysis": "ടെക്നിക്കൽ അനാലിസിസ്", "Strategy": "സ്ട്രാറ്റജി",
         "Trading Psychology": "ട്രേഡിംഗ് സൈക്കോളജി", "Market Update": "മാർക്കറ്റ് അപ്ഡേറ്റ്",
         "Brokers & Accounts": "ബ്രോക്കർ & അക്കൗണ്ട്", "IB Rebates": "IB റിബേറ്റ്"}),
 "ta": dict(name="தமிழ்", font="Noto Sans Tamil", base="/ta/blog/",
   months="ஜனவரி பிப்ரவரி மார்ச் ஏப்ரல் மே ஜூன் ஜூலை ஆகஸ்ட் செப்டம்பர் அக்டோபர் நவம்பர் டிசம்பர்".split(),
   blog="வலைப்பதிவு", home="முகப்பு", by="எழுதியவர்: XDemat", min_read="{n} நிமிட வாசிப்பு", read="கட்டுரையைப் படிக்க", keep="மேலும் படிக்க",
   idx_title="XAUUSD தங்க வர்த்தக வலைப்பதிவு தமிழில் | XRover 5",
   idx_desc="XAUUSD (தங்கம்) வர்த்தகத்தை தமிழில் கற்றுக்கொள்ளுங்கள்: வர்த்தக நேரம், லாட் அளவு, ரிஸ்க் மேலாண்மை, கேண்டில்ஸ்டிக், புரோக்கர், IB ரிபேட் பற்றிய எளிய வழிகாட்டிகள்.",
   idx_h1="XAUUSD வர்த்தகம் தமிழில்",
   idx_lead="தங்க வர்த்தகம் பற்றிய நடைமுறை, நேர்மையான வழிகாட்டிகள்: எப்போது வர்த்தகம் செய்வது, பொசிஷன் அளவை எப்படி கணக்கிடுவது, விலை ஏன் மாறுகிறது, கேண்டில்களை எப்படி படிப்பது, உங்கள் மூலதனத்தை எப்படி பாதுகாப்பது.",
   soon="புதிய கட்டுரைகள் விரைவில் வருகின்றன.",
   cta_h2="உண்மையான சந்தையில் XRover 5 எப்படி XAUUSD வர்த்தகம் செய்கிறது என்று பாருங்கள்",
   cta_p="உறுதிசெய்யப்பட்ட என்ட்ரிகள், அடாப்டிவ் சோன்கள், ஸ்டாப்-லாஸ் மற்றும் டார்கெட் மேலாண்மை, டெலிகிராம் அலர்ட்கள் பற்றிய முழு விளக்கத்திற்கும் உங்கள் கேள்விகளுக்கான பதில்களுக்கும் ஒரு லைவ் டெமோவை புக் செய்யுங்கள்.",
   demo="டெமோ புக் செய்யுங்கள்", features="அம்சங்களைப் பாருங்கள்",
   risk="இந்தக் கட்டுரை கல்வி நோக்கத்திற்காக மட்டுமே; இது நிதி ஆலோசனை அல்ல. லீவரேஜ் பயன்படுத்தி தங்கம் மற்றும் பிற CFD-களை வர்த்தகம் செய்வதில் பணத்தை இழக்கும் அதிக ஆபத்து உள்ளது. மேலே உள்ள விலைகளும் உதாரணங்களும் விளக்கத்திற்காக மட்டுமே.",
   b_eye="நாங்கள் பரிந்துரைக்கும் புரோக்கர்கள்", b_h="உங்கள் டிரேடிங் கணக்கைத் திறங்கள்",
   b_p='XRover 5 உறுப்பினர்களுக்கு நாங்கள் பரிந்துரைக்கும் புரோக்கர்கள் இவை. இந்த லிங்குகள் மூலம் கணக்கைத் திறந்தால் XRover 5 சிறப்பு விலைக்கும் <a href="/rebate/" style="color:var(--cyan)">XDemat IB ரிபேட்டுக்கும்</a> தகுதி பெறலாம்.',
   b_open="கணக்கைத் திறக்க ↗", b_offer="சிறப்பு சலுகையை எப்படி பெறுவது", b_contact="கணக்கைத் திறந்துவிட்டீர்களா? எங்களைத் தொடர்புகொள்ளுங்கள்",
   b_disc="<b>வெளிப்படுத்தல்:</b> இவை பார்ட்னர் லிங்குகள். இந்த லிங்குகள் மூலம் நீங்கள் கணக்கைத் திறந்தால் புரோக்கரிடமிருந்து XDemat-க்கு கமிஷன் கிடைக்கலாம்; இதற்கு உங்களுக்கு கூடுதல் செலவு இல்லை. புரோக்கர் கணக்குகளை வழங்குவது புரோக்கர்கள், XDemat அல்ல. உங்களுக்குப் பொருந்தும் விதிகளின்படி (இந்திய குடியிருப்பாளர்களுக்கு RBI/FEMA விதிகள்) ஒரு புரோக்கருடன் வர்த்தகம் செய்ய அனுமதி உள்ளதா என்று சரிபார்த்து, கணக்கைத் திறப்பதற்கு முன் புரோக்கரின் நிபந்தனைகளைப் படியுங்கள். ஃபாரெக்ஸ் மற்றும் CFD வர்த்தகத்தில் பணத்தை இழக்கும் அதிக ஆபத்து உள்ளது.",
   r_eye="XDemat IB ரிபேட் திட்டம்", r_h="உங்கள் வர்த்தக கமிஷனின் ஒரு பகுதியை USDT-ஆக திரும்பப் பெறுங்கள்",
   r_p="XDemat மூலம் ஒரு பார்ட்னர் புரோக்கரிடம் MT4 அல்லது MT5 கணக்கைத் திறந்து, அதை ரீட்-ஒன்லியாக இணைக்கவும். நீங்கள் வர்த்தகம் செய்யும் ஒவ்வொரு லாட்டிலும் IB கமிஷனின் ஒரு பங்கு உங்கள் கிரிப்டோ வாலட்டுக்கு வழங்கப்படும். உங்களுக்கு கூடுதல் செலவு இல்லை.",
   r_reg="இலவச ரிபேட் கணக்கை உருவாக்குங்கள்", r_how="இது எப்படி செயல்படுகிறது",
   r_small="ரிபேட் புரோக்கர் உண்மையில் செலுத்தும் கமிஷனைப் பொறுத்தது; இது வர்த்தக ஆபத்தைக் குறைக்காது. புரோக்கரும் கிரிப்டோ பேஅவுட்களும் உங்கள் நாட்டில் அனுமதிக்கப்படுகிறதா என்றும், ரிபேட்டுக்கு வரி உண்டா என்றும் சரிபாருங்கள்.",
   cats={"Beginner Guide": "ஆரம்பநிலை வழிகாட்டி", "Trading Sessions": "வர்த்தக நேரங்கள்", "Risk & Money": "ரிஸ்க் & பணம்",
         "Fundamentals": "அடிப்படை பகுப்பாய்வு", "Technical Analysis": "டெக்னிக்கல் அனாலிசிஸ்", "Strategy": "உத்தி",
         "Trading Psychology": "வர்த்தக மனநிலை", "Market Update": "சந்தை நிலவரம்",
         "Brokers & Accounts": "புரோக்கர் & கணக்கு", "IB Rebates": "IB ரிபேட்"}),
 "hi": dict(name="हिन्दी", font="Noto Sans Devanagari", base="/hi/blog/",
   months="जनवरी फ़रवरी मार्च अप्रैल मई जून जुलाई अगस्त सितंबर अक्टूबर नवंबर दिसंबर".split(),
   blog="ब्लॉग", home="होम", by="लेखक: XDemat", min_read="{n} मिनट में पढ़ें", read="लेख पढ़ें", keep="और पढ़ें",
   idx_title="XAUUSD गोल्ड ट्रेडिंग ब्लॉग हिन्दी में | XRover 5",
   idx_desc="XAUUSD (गोल्ड) ट्रेडिंग हिन्दी में सीखें: ट्रेडिंग का समय, लॉट साइज़, रिस्क मैनेजमेंट, कैंडलस्टिक, ब्रोकर और IB रिबेट पर आसान गाइड।",
   idx_h1="XAUUSD ट्रेडिंग हिन्दी में",
   idx_lead="गोल्ड ट्रेडिंग पर व्यावहारिक और ईमानदार गाइड: कब ट्रेड करें, पोज़िशन साइज़ कैसे तय करें, कीमत क्यों बदलती है, कैंडल कैसे पढ़ें और अपनी पूंजी कैसे बचाएँ।",
   soon="नए लेख जल्द आ रहे हैं।",
   cta_h2="देखें कि XRover 5 असली मार्केट में XAUUSD कैसे ट्रेड करता है",
   cta_p="कन्फ़र्म्ड एंट्री, अडैप्टिव ज़ोन, स्टॉप-लॉस और टारगेट मैनेजमेंट तथा टेलीग्राम अलर्ट की पूरी जानकारी और आपके सवालों के जवाब के लिए लाइव डेमो बुक करें।",
   demo="डेमो बुक करें", features="फ़ीचर देखें",
   risk="यह लेख केवल शिक्षा के लिए है, यह वित्तीय सलाह नहीं है। लीवरेज के साथ गोल्ड और अन्य CFD ट्रेड करने में पैसा गँवाने का बहुत अधिक जोखिम होता है। ऊपर दी गई कीमतें और उदाहरण केवल समझाने के लिए हैं।",
   b_eye="हमारे सुझाए गए ब्रोकर", b_h="अपना ट्रेडिंग अकाउंट खोलें",
   b_p='ये वे ब्रोकर हैं जिन्हें हम XRover 5 सदस्यों को सुझाते हैं। इन लिंक से अकाउंट खोलने पर आप XRover 5 की स्पेशल प्राइसिंग और <a href="/rebate/" style="color:var(--cyan)">XDemat IB रिबेट</a> के लिए पात्र हो सकते हैं।',
   b_open="अकाउंट खोलें ↗", b_offer="स्पेशल ऑफ़र कैसे पाएँ", b_contact="अकाउंट खोल लिया? हमसे संपर्क करें",
   b_disc="<b>खुलासा:</b> ये पार्टनर लिंक हैं। इन लिंक से अकाउंट खोलने पर XDemat को ब्रोकर से कमीशन मिल सकता है; इसके लिए आपको कोई अतिरिक्त खर्च नहीं देना होता। ब्रोकर अकाउंट ब्रोकर देते हैं, XDemat नहीं। जाँच लें कि आप पर लागू नियमों के तहत (भारतीय निवासियों के लिए RBI/FEMA नियम) किसी ब्रोकर के साथ ट्रेड करने की अनुमति है, और अकाउंट खोलने से पहले ब्रोकर की शर्तें पढ़ें। फ़ॉरेक्स और CFD ट्रेडिंग में पैसा गँवाने का बहुत अधिक जोखिम होता है।",
   r_eye="XDemat IB रिबेट प्रोग्राम", r_h="अपने ट्रेडिंग कमीशन का एक हिस्सा USDT में वापस पाएँ",
   r_p="XDemat के ज़रिए किसी पार्टनर ब्रोकर के साथ MT4 या MT5 अकाउंट खोलें और उसे रीड-ओनली जोड़ें। आपके हर ट्रेड किए गए लॉट पर IB कमीशन का एक हिस्सा आपके क्रिप्टो वॉलेट में दिया जाता है। आपको कोई अतिरिक्त खर्च नहीं।",
   r_reg="मुफ़्त रिबेट अकाउंट बनाएँ", r_how="यह कैसे काम करता है",
   r_small="रिबेट इस पर निर्भर है कि ब्रोकर असल में कितना कमीशन देता है; इससे ट्रेडिंग का जोखिम कम नहीं होता। जाँच लें कि ब्रोकर और क्रिप्टो पेआउट आपके देश में अनुमत हैं, और रिबेट पर टैक्स लगता है या नहीं।",
   cats={"Beginner Guide": "शुरुआती गाइड", "Trading Sessions": "ट्रेडिंग सेशन", "Risk & Money": "रिस्क और मनी",
         "Fundamentals": "फंडामेंटल्स", "Technical Analysis": "टेक्निकल एनालिसिस", "Strategy": "स्ट्रैटेजी",
         "Trading Psychology": "ट्रेडिंग साइकोलॉजी", "Market Update": "मार्केट अपडेट",
         "Brokers & Accounts": "ब्रोकर और अकाउंट", "IB Rebates": "IB रिबेट"}),
}
LANGS = list(L)

def fdate(d, lang):
    m = L[lang]["months"][d.month - 1]
    return f"{d.day} {m} {d.year}"

# ---------------------------------------------------------------- load sources
def front(f):
    raw = open(f, encoding="utf-8").read()
    m = re.match(r"\s*<!--(.*?)-->(.*)", raw, re.S)
    if not m: sys.exit(f"{f}: missing <!-- front matter -->")
    return dict(re.findall(r"^\s*(\w+):\s*(.+?)\s*$", m.group(1), re.M)), m.group(2)

def load():
    posts = {lang: [] for lang in LANGS}
    en = {}
    for f in sorted(glob.glob(os.path.join(ROOT, "_blog", "posts", "*.html"))):
        meta, body = front(f)
        miss = [k for k in REQUIRED if k not in meta]
        if miss: sys.exit(f"{f}: missing {miss}")
        if not re.fullmatch(r"[a-z0-9-]+", meta["slug"]): sys.exit(f"{f}: bad slug")
        if meta["slug"] in en: sys.exit(f"duplicate slug: {meta['slug']}")
        d = datetime.date.fromisoformat(meta["date"])
        p = {"lang": "en", "slug": meta["slug"], "cat_en": meta["category"], "title": meta["title"], "desc": meta["description"],
             "d": d, "body": body, "brokers": FLAG(meta.get("brokers", "")), "rebate": FLAG(meta.get("rebate", ""))}
        en[p["slug"]] = p
        posts["en"].append(p)
    for lang in LANGS[1:]:
        seen = set()
        for f in sorted(glob.glob(os.path.join(ROOT, "_blog", "posts", lang, "*.html"))):
            meta, body = front(f)
            miss = [k for k in REQUIRED_TR if k not in meta]
            if miss: sys.exit(f"{f}: missing {miss}")
            o = en.get(meta["slug"])
            if not o: sys.exit(f"{f}: no English post with slug {meta['slug']}")
            if meta["slug"] in seen: sys.exit(f"{f}: duplicate {lang} translation of {meta['slug']}")
            seen.add(meta["slug"])
            d = datetime.date.fromisoformat(meta["date"]) if "date" in meta else o["d"]
            posts[lang].append({"lang": lang, "slug": o["slug"], "cat_en": o["cat_en"], "title": meta["title"], "desc": meta["description"],
                                "d": d, "body": body, "brokers": o["brokers"], "rebate": o["rebate"]})
    for lang in LANGS:
        for p in posts[lang]:
            p["cat"] = L[lang]["cats"].get(p["cat_en"], p["cat_en"])
            p["date"] = p["d"].isoformat()
            p["date_h"] = fdate(p["d"], lang)
            p["path"] = L[lang]["base"] + p["slug"] + "/"
        posts[lang].sort(key=lambda p: (p["date"], p["slug"]), reverse=True)
    return posts

POSTS = load()
AVAIL = {lang: {p["slug"] for p in POSTS[lang]} for lang in LANGS}

# ---------------------------------------------------------------- shared page parts
src = open(f"{ROOT}/contact/index.html", encoding="utf-8").read()
base_css = re.search(r'(<style>\n/\* Layout:[\s\S]*?</style>)', src).group(1)
header = re.search(r'(<header class="nav">[\s\S]*?</header>)', src).group(1)
footer = re.search(r'(<footer>[\s\S]*?</footer>)', src).group(1)
if '/blog/' not in header:
    header = header.replace('<a href="/contact/" data-route="contact">Contact Us</a>',
        '<a href="/blog/" data-route="blog">Blog</a>\n      <a href="/contact/" data-route="contact">Contact Us</a>')
header = re.sub(r'<a href="/blog/"[^>]*>Blog</a>', '<a href="/blog/" aria-current="page">Blog</a>', header)
header = re.sub(r' data-route="\w+"', '', header)
if '/blog/' not in footer:
    footer = footer.replace('<a href="/services/">Services</a>', '<a href="/services/">Services</a><a href="/blog/">Blog</a>')

BLOG_CSS = """<style>
/* BLOG */
.blog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr)); gap: 20px; }
.post-card { text-decoration: none; transition: border-color .2s, transform .2s; }
.post-card:hover { border-color: color-mix(in oklab, var(--ember) 55%, var(--line)); transform: translateY(-2px); }
.post-card .tag, .post-meta .tag { font-family: var(--mono); font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: var(--ember); }
.post-card h2 { font-size: 1.15rem; font-weight: 600; line-height: 1.3; }
.post-card .more { color: var(--cyan); font-weight: 600; font-size: 14px; }
.post-meta { display: flex; flex-wrap: wrap; gap: 8px 18px; align-items: center; color: var(--muted); font-size: 14px; }
.crumbs { font-size: 14px; color: var(--muted); }
.crumbs a { color: var(--muted); text-decoration: none; } .crumbs a:hover { color: var(--fg); }
.langs { display: flex; flex-wrap: wrap; gap: 8px; }
.langs a { padding: 6px 14px; border: 1px solid var(--line); border-radius: 999px; text-decoration: none; color: var(--muted); font-size: 14px; font-weight: 600; }
.langs a:hover { color: var(--fg); }
.langs a[aria-current] { color: var(--fg); background: var(--surface-2); border-color: color-mix(in oklab, var(--cyan) 50%, var(--line)); }
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

def lang_css(lang):
    f = L[lang]["font"]
    if not f: return ""
    # Indic scripts: add the Noto font after the Latin fonts, no letter-spacing/uppercase, more line height
    return f"""<style>
:root {{ --display: "Unbounded", "{f}", system-ui, sans-serif; --body: "Manrope", "{f}", system-ui, sans-serif; }}
h1, h2, h3 {{ line-height: 1.35; letter-spacing: 0; }}
.page-head h1 {{ font-weight: 700; }}
.eyebrow, .post-card .tag, .post-meta .tag {{ letter-spacing: .02em; text-transform: none; font-family: var(--body); font-size: 13px; }}
.article {{ line-height: 1.9; }}
.lead {{ line-height: 1.8; }}
</style>"""

def font_link(lang):
    f = L[lang]["font"]
    fam = "family=Unbounded:wght@400;600;800&family=Manrope:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500"
    if f: fam += "&family=" + f.replace(" ", "+") + ":wght@400;500;600;700;800"
    return f'<link rel="stylesheet" href="https://fonts.googleapis.com/css2?{fam}&display=swap">'

def brokers_box(lang):
    s = L[lang]
    return f"""<aside class="brokers" aria-labelledby="rb-title" style="margin-top:40px;max-width:760px">
        <div class="brokers-head">
          <span class="eyebrow">{s['b_eye']}</span>
          <h3 id="rb-title">{s['b_h']}</h3>
          <p>{s['b_p']}</p>
        </div>
        <div class="broker-grid" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr))">
          <a class="broker" href="https://clickto.trade/bWvhiqPpr8h?ib=7377800" target="_blank" rel="noopener sponsored">
            <span class="blogo" style="background:#c4c0ad"><img src="/assets/broker-octa.png" alt="Octa" width="177" height="64" loading="lazy"></span>
            <span class="bname">Octa</span>
            <span class="bcta">{s['b_open']}</span>
          </a>
          <a class="broker" href="https://cabinet.switchmarkets.com/links/go/14777" target="_blank" rel="noopener sponsored">
            <span class="blogo" style="background:#000c2e"><img src="/assets/broker-switch.png" alt="Switch Markets" width="260" height="82" loading="lazy"></span>
            <span class="bname">Switch Markets</span>
            <span class="bcta">{s['b_open']}</span>
          </a>
        </div>
        <div class="btn-row"><a class="btn btn-ghost" href="/products/#brokers">{s['b_offer']}</a><a class="btn btn-ghost" href="/contact/">{s['b_contact']}</a></div>
        <p class="broker-risk">{s['b_disc']}</p>
      </aside>"""
# Broker partner (IB) links live ONLY in brokers_box above — same links as on /products/.

def rebate_box(lang):
    s = L[lang]
    return f"""<aside class="post-cta" style="margin-top:40px;border-color:color-mix(in oklab, var(--bull) 45%, var(--line))">
        <span class="eyebrow">{s['r_eye']}</span>
        <h2>{s['r_h']}</h2>
        <p>{s['r_p']}</p>
        <div class="btn-row"><a class="btn btn-primary" href="/rebate/register.php">{s['r_reg']}</a><a class="btn btn-ghost" href="/rebate/">{s['r_how']}</a></div>
        <p style="font-size:13px">{s['r_small']}</p>
      </aside>"""

MENU_JS = """<script>
(function(){var b=document.getElementById('menuBtn'),l=document.getElementById('links');
b.addEventListener('click',function(){var o=l.classList.toggle('open');b.setAttribute('aria-expanded',String(o));});})();
</script>"""

def words(h):
    t = re.sub(r'<[^>]+>', ' ', h)
    return len(t.split())

def alternates(paths):
    """paths: {lang: path}. Returns hreflang link tags."""
    out = [f'<link rel="alternate" hreflang="{lg}" href="{SITE}{pth}">' for lg, pth in paths.items()]
    if "en" in paths: out.append(f'<link rel="alternate" hreflang="x-default" href="{SITE}{paths["en"]}">')
    return "\n".join(out)

def lang_nav(paths, cur):
    links = "".join(f'<a href="{paths[lg]}" hreflang="{lg}" lang="{lg}"{" aria-current=\"true\"" if lg == cur else ""}>{L[lg]["name"]}</a>'
                    for lg in LANGS if lg in paths)
    return f'<nav class="langs" aria-label="Language">{links}</nav>' if len(paths) > 1 else ""

def head(lang, title, desc, path, ogtype, ld, paths):
    t, d = html.escape(title), html.escape(desc)
    url = SITE + path
    locale = {"en": "en_US", "ml": "ml_IN", "ta": "ta_IN", "hi": "hi_IN"}[lang]
    return f"""<!doctype html>
<html lang="{lang}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{t}</title>
<meta name="description" content="{d}">
<link rel="canonical" href="{url}">
{alternates(paths)}
<meta name="robots" content="index, follow, max-image-preview:large">
<meta name="theme-color" content="#0a0b12">
<meta property="og:type" content="{ogtype}">
<meta property="og:site_name" content="XRover 5">
<meta property="og:locale" content="{locale}">
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
{font_link(lang)}
{base_css}
{BLOG_CSS}
{lang_css(lang)}
</head>
<body>
{header}
"""

def tail(): return f"\n{footer}\n\n{MENU_JS}\n</body>\n</html>\n"

def card(p):
    s = L[p["lang"]]
    mins = max(3, round(words(p['body']) / 200))
    return f"""<a class="card post-card" href="{p['path']}">
        <span class="tag">{html.escape(p['cat'])}</span>
        <h2>{html.escape(p['title'])}</h2>
        <p>{html.escape(p['desc'])}</p>
        <span class="more"><time datetime="{p['date']}">{p['date_h']}</time> · {s['read']} · {s['min_read'].format(n=mins)} →</span>
      </a>"""

def localise_links(body, lang):
    """In a translation, point /blog/<slug>/ links to the same-language version when it exists."""
    if lang == "en": return body
    def sub(m):
        slug = m.group(1)
        return f'href="{L[lang]["base"]}{slug}/"' if slug in AVAIL[lang] else m.group(0)
    return re.sub(r'href="/blog/([a-z0-9-]+)/"', sub, body)

ORG = {"@type": "Organization", "name": "XDemat", "url": "https://xrover5.com/", "logo": "https://xrover5.com/assets/xdemat-logo.webp"}

# ---------------------------------------------------------------- build
written = set()
for lang in LANGS:
    s = L[lang]
    for p in POSTS[lang]:
        paths = {lg: L[lg]["base"] + p["slug"] + "/" for lg in LANGS if p["slug"] in AVAIL[lg]}
        url = SITE + p["path"]
        mins = max(3, round(words(p['body']) / 200))
        ld = {"@context": "https://schema.org", "@graph": [
            {"@type": "BlogPosting", "headline": p['title'], "description": p['desc'], "image": "https://xrover5.com/assets/og-image.jpg",
             "datePublished": p["date"], "dateModified": p["date"], "author": ORG, "publisher": ORG, "mainEntityOfPage": url, "inLanguage": lang},
            {"@type": "BreadcrumbList", "itemListElement": [
                {"@type": "ListItem", "position": 1, "name": s["home"], "item": SITE + "/"},
                {"@type": "ListItem", "position": 2, "name": s["blog"], "item": SITE + s["base"]},
                {"@type": "ListItem", "position": 3, "name": p['title'], "item": url}]}]}
        others = [q for q in POSTS[lang] if q is not p]
        related = ([q for q in others if q["cat_en"] == p["cat_en"]] + [q for q in others if q["cat_en"] != p["cat_en"]])[:3]
        page = head(lang, f"{p['title']} | XRover 5 {s['blog'] if lang != 'en' else 'Blog'}", p['desc'], p["path"], "article", ld, paths) + f"""
<main>
<div class="page">
  <div class="wrap page-head">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="/">{s['home']}</a> / <a href="{s['base']}">{s['blog']}</a> / {html.escape(p['cat'])}</nav>
    <h1 style="font-size:clamp(1.9rem,4.4vw,3.1rem);max-width:22ch">{html.escape(p['title'])}</h1>
    <div class="post-meta"><span class="tag">{html.escape(p['cat'])}</span><span>{s['by']}</span><time datetime="{p['date']}">{p['date_h']}</time><span>{s['min_read'].format(n=mins)}</span></div>
    {lang_nav(paths, lang)}
  </div>
  <section class="band" style="padding-top:28px">
    <div class="wrap">
      <article class="article">
{localise_links(p['body'].strip(), lang)}
      </article>
      {rebate_box(lang) if p["rebate"] else ""}
      {brokers_box(lang) if p["brokers"] else ""}
      <div class="post-cta">
        <span class="eyebrow">XRover 5 · Intelligent Candle Trading System</span>
        <h2>{s['cta_h2']}</h2>
        <p>{s['cta_p']}</p>
        <div class="btn-row"><a class="btn btn-primary" href="/contact/">{s['demo']}</a><a class="btn btn-ghost" href="/products/">{s['features']}</a></div>
      </div>
      <p class="post-risk">{s['risk']}</p>
      {f'''<div class="related">
        <h2>{s['keep']}</h2>
        <div class="blog-grid">
      {''.join(card(r) for r in related)}
        </div>
      </div>''' if related else ''}
    </div>
  </section>
</div>
</main>
""" + tail()
        out = ROOT + p["path"]
        os.makedirs(out, exist_ok=True)
        open(out + "index.html", "w", encoding="utf-8").write(page)
        written.add(os.path.normpath(out))

    # Index for this language
    idx_paths = {lg: L[lg]["base"] for lg in LANGS if lg == "en" or POSTS[lg]}
    ld = {"@context": "https://schema.org", "@type": "Blog", "name": s["idx_title"], "url": SITE + s["base"], "inLanguage": lang, "publisher": ORG,
          "blogPost": [{"@type": "BlogPosting", "headline": p['title'], "url": SITE + p["path"], "datePublished": p["date"]} for p in POSTS[lang]]}
    grid = ''.join(card(p) for p in POSTS[lang]) or f'<p class="lead">{s["soon"]}</p>'
    idx = head(lang, s["idx_title"], s["idx_desc"], s["base"], "website", ld, idx_paths) + f"""
<main>
<div class="page">
  <div class="wrap page-head">
    <span class="eyebrow">{s['blog']}</span>
    <h1>{s['idx_h1']}</h1>
    <p class="lead">{s['idx_lead']}</p>
    {lang_nav(idx_paths, lang)}
  </div>
  <section class="band" style="padding-top:36px">
    <div class="wrap">
      <div class="blog-grid">
      {grid}
      </div>
    </div>
  </section>
</div>
</main>
""" + tail()
    os.makedirs(ROOT + s["base"], exist_ok=True)
    open(ROOT + s["base"] + "index.html", "w", encoding="utf-8").write(idx)

# Remove pages of posts that no longer exist
for lang in LANGS:
    for d in glob.glob(ROOT + L[lang]["base"] + "*/"):
        if os.path.normpath(d) not in written:
            shutil.rmtree(d)

# Sitemap: keep non-blog entries, rewrite blog entries (all languages)
sm = open(f"{ROOT}/sitemap.xml", encoding="utf-8").read()
sm = re.sub(r"\s*<url><loc>https://xrover5\.com/(?:(?:ml|ta|hi)/)?blog/.*?</url>", "", sm)
lines = []
for lang in LANGS:
    if lang != "en" and not POSTS[lang]: continue
    latest = POSTS[lang][0]["date"] if POSTS[lang] else datetime.date.today().isoformat()
    lines.append(f"  <url><loc>{SITE}{L[lang]['base']}</loc><lastmod>{latest}</lastmod><priority>{'0.8' if lang == 'en' else '0.7'}</priority></url>")
    lines += [f"  <url><loc>{SITE}{p['path']}</loc><lastmod>{p['date']}</lastmod><priority>{'0.7' if lang == 'en' else '0.6'}</priority></url>" for p in POSTS[lang]]
sm = re.sub(r"\s*</urlset>", "\n" + "\n".join(lines) + "\n</urlset>", sm)
open(f"{ROOT}/sitemap.xml", "w", encoding="utf-8").write(sm)

print("built:", ", ".join(f"{lang} {len(POSTS[lang])}" for lang in LANGS))
missing = {lang: sorted(AVAIL["en"] - AVAIL[lang]) for lang in LANGS[1:]}
for lang, m in missing.items():
    if m: print(f"  {lang}: {len(m)} English post(s) not translated yet: {', '.join(m)}")
