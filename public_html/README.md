# CCS Solutions — Hybrid Care. Recharged.

A responsive, buildless website for CCS Solutions in Ragama, Sri Lanka. The downloadable ZIP puts all website files directly at its root: extract it and open `index.html`, or upload the extracted files to a static host. No package installation or build is needed. Keep the assets folders beside the HTML files.

## Pages and interactions

- Home: once-per-session, two-poster welcome promotion carousel, orbital startup loader, loading counter and elapsed timer, supplied seven-second introduction, six cinematic scroll chapters, photographic “From Symptom to Solution” workflow, service details, battery animations, model preview, Google review slideshow, owner section, inspection form and map.
- Car models: 50 representative hybrid, mild-hybrid and plug-in models across 12 makes, searchable by brand, name, chassis or hybrid type. Each car has a white presentation field, a compact PNG reference and an optimized WebP. Petrol-only Wagon R FA is excluded. This is an enquiry directory; confirm parts and workshop availability for the exact variant.
- Videos: six company workshop videos: three Facebook reels and three TikTok posts, plus four company artwork cards (two Facebook adverts and two supplied promotions). Local previews, Facebook / TikTok filters, original-post links and an on-demand embedded player. The generic cinematic car clips stay on the homepage and are excluded from this workshop video page.
- Offers: the two exact client-supplied 20% promotional posters, with service-specific enquiry links, followed by battery, ABS and diagnostics service spotlights. The promotion eligibility and pricing are confirmed with the workshop; no expiry date is invented.
- Warranty: guidance to check the written terms for the selected work, plus a warranty enquiry form. The workshop decides coverage; the site does not invent warranty periods or approve claims.
- Contact: phone, ccssolutionshybrid@gmail.com, direct WhatsApp, exact Google business pin and the full inspection request form. Model and service selections carry across from relevant pages.
- Login: a light glass panel with the form on the left and the three client-supplied car photographs in an adjacent slideshow on the right with responsive nav, email/phone/vehicle number + password, create-account mode, reset requests, and Google OAuth controls. Real sign-in requires a workshop-owned Supabase authentication setup; see `SETUP_LOGIN.md`. The hosted Site native login is retained only on that platform.
- Visit planner: useful workshop links and a vehicle note stored only in this browser. It does not show appointments, invoices or customer records.
- Sources: photography, media and business-information credits.

All pages include fixed desktop navigation and a fixed three-line mobile menu, direct company Facebook and TikTok links, a floating WhatsApp contact panel, a local website assistant above it, and a motion preference. Browser reduced-motion settings are also respected. The interior video pauses offscreen; the review slideshow has manual controls and an explicit pause button; it resumes its timer after manual arrows. The removed introduction replay/enter/time bar and interior pause button are not included.

## Inspection and warranty enquiries

The forms validate entries and show a message preview. For an inspection or service enquiry, customers choose WhatsApp or email. The inspection form shows both prepared links and a copy-request fallback. Email opens the customer’s mail app addressed to ccssolutionshybrid@gmail.com with the full request and vehicle in the subject. The customer presses Send in the chosen app; the site never claims delivery. The warranty enquiry continues through WhatsApp. The workshop confirms an appointment, price, compatibility and any warranty directly. No message is sent automatically. There is no booking database, payment collection or server-side customer-record storage.

## Sign-in and hosting

The new login screen is styled and its controls are wired to a **configurable, optional Supabase authentication provider**. No accounts are pre-created, and without project configuration its login actions remain disabled with an explicit explanation. Email+password, verified-phone+password, Google OAuth and a separately rate-limited, staff-verified vehicle number alias are available once set up on an HTTPS host. Read `SETUP_LOGIN.md` and the included SQL/Edge Function. The old native preview sign-in remains as a fallback on `*.chatgpt.site` only. This static website does not itself store passwords or secure private account records.

The visit planner is still a device-only note, not an appointment database or personal records service.

## Business details and reviews

- CCS Solutions — Hybrid Battery Repair
- 318/A Maharanugegoda Road, Ragama 11850, Sri Lanka
- +94 71 793 7011
- ccssolutionshybrid@gmail.com (supplied by the client on 29 October 2026)
- Google Maps: https://www.google.com/maps?cid=11273773956273965881
- Facebook: https://www.facebook.com/profile.php?id=61567738112126
- TikTok: https://www.tiktok.com/@ccs.solutions1

The exact supplied Google listing labels the postal address as Ragama, although the client located it through a Kadawatha search. The map uses the business CID rather than a general location search.

Google was checked on 28 October 2026: 4.9 / 5 from 73 reviews. The slideshow uses three short, publicly verified excerpts by Malaka Nuwan, Gayan Buddika and A&C events DJ Ashley. Google required sign-in to reveal more reviews, so no additional reviews were copied or invented. This is a dated snapshot, not a live feed. Refresh the page and `assets/google-review-sources.json` together when adding an authorised export or newly verified reviews.

## Images and media

Display images and homepage cinematic/interior videos are local. Barlow Condensed and Manrope use a Google Fonts stylesheet with system fallbacks; fonts are not bundled. The workshop video players, map, social links, sign-in and WhatsApp need an internet connection.

Vehicle images are representative model-family or trim photographs. Some pictures show one generation from a listed family; parts and chassis must be confirmed with the workshop. Compact files are at most 900px wide, with no source upscaling during export. Empty white margins were trimmed, and the original photo content was resized and padded on white. Source dimensions, credited pages and available licence information are recorded in `assets/vehicles/image-sources.json`. Lower-resolution references retain their native detail. Large vehicle masters are excluded to keep the ZIP manageable. Manufacturer marks identify makes and do not imply affiliation.

The logo, owner portrait, introduction and scroll film came from the client's supplied files. The latest client-supplied interior clip replaces the previous photograph. It is compressed locally, with a poster from the same clip, and is not claimed to show CCS premises. Charitha Jayadeep Duwage's name and owner role came from the correction brief. Identity and social provenance are in `assets/identity-sources.json`.

Service and workflow photos illustrate the work discussed. Repair examples are clearly labelled scenarios. Sources are credited on `sources.html` and in the included source records. Pexels images retain their original licence: https://www.pexels.com/license/. The hybrid battery pack image is TTTNIS's public-domain Toyota Prius PHV battery photograph from Wikimedia Commons. Badges use Simple Icons and a CC0 Lexus reference. Barlow Condensed and Manrope are loaded from Google Fonts when online, with local system fallback.

## Latest correction details

The welcome carousel changes every 5.5 seconds while visible; manual selection, pause, close, continue and offer enquiry actions work independently. Reduced-motion and global motion-off preferences disable automatic slide changes. The review carousel advances every 6.5 seconds while in view; the explicit Pause button, motion settings and page visibility control autoplay.

The original four company video previews were checked on Facebook and TikTok on 28 October 2026; two additional Facebook reels and two original Facebook advertising images were added on 30 October 2026. Preview images are included locally; playback stays with the source platform and requires internet access. `assets/social/video-sources.json` records the exact sources. The posters were supplied by the client in the latest correction brief; their full PNG versions and compact WebP display versions are in `assets/promotions/`.

The request for at least ten Google reviews still requires further source access. Only the three public, verified reviews are included. Opening Google sign-in was blocked by automatic approval review because personal-account access had not been explicitly authorised. No extra reviewer names, ratings or comments were invented.

## 29 October corrections — assistant, navigation, login and email

`correcting 5.zip` supplied the four requirements. `platform-chatbot-for-website.webp` and `image(20261029-103104).png` were design references for the assistant, not CCS photographs or brand assets. The implementation follows the existing dark teal and gold CCS identity.

- Navigation stays fixed on every page, with appropriate anchor offsets and a three-line menu below 1001px. The cinematic chapter label is positioned below the persistent header.
- Login navigates in the same tab to `login.html`. The page keeps native Sites sign-in rather than collecting a customer password. No new account service or fake email/password authentication has been created. The existing owner-private audience is preserved.
- The supplied email address appears on Contact, the homepage and every footer. Both homepage and Contact inspection forms validate, preview and prepare WhatsApp and email requests. There is no automated email provider, delivery receipt, background messaging or appointment database.

### Free on-device website assistant

The chat button sits immediately above WhatsApp and shows a dismissible greeting. It works across all nine pages with keyboard controls, suggested questions, source-page links, a reset button and a readable scrolling conversation. It never submits an inspection or sends an email itself.

This is a curated retrieval assistant, not an unrestricted generative chatbot. Transformers.js 3.8.1 and a quantized all-MiniLM-L6-v2 sentence-transformer run in a Web Worker using ONNX Runtime Web / WebAssembly. Matching selects from the original CCS topics plus broader automotive/everyday topics in `assistant-data.js`; it does not generate new repair facts. It now also answers simple arithmetic, date/time (from the customer device) and a few small-talk queries. Model-specific answers use the actual 50-model directory. Unknown or unrelated requests receive a scope-limited reply. Prices, hours, live stock, appointments and warranty periods are never invented.

All assistant model, tokenizer and runtime files are bundled in `assets/assistant/`. No API key, subscription, external chatbot account, inference endpoint or third-party conversation transmission is needed. The first chat opening loads approximately 46 MB of local model/runtime assets; they do not load with the homepage. Browsers supporting Cache Storage can cache the model. Run from HTTP(S) for AI matching. Opening `index.html` directly with file:// provides the included quick-answer fallback because module workers cannot run there. Unsupported browsers or engine errors also keep quick answers available. Chat text stays only in page memory and is discarded on navigation/reload; it is not sent to CCS.

The model is Apache-2.0 licensed, Transformers.js is Apache-2.0, and ONNX Runtime is MIT. Licence files, the model card and exact download sources are included. Model source: https://huggingface.co/Xenova/all-MiniLM-L6-v2. Runtime documentation: https://huggingface.co/docs/transformers.js/v3.8.1/index.

To edit business facts, change the website and `assistant-data.js` together. No training service is required. Keep the helper scripts, worker and the complete `assets/assistant/` directory when uploading this ZIP to another static host.

## 30 October — Round 8 corrections

- Rebuilt customer login on an automotive photo/video background with more prominent navigation, polished form fields, clear sign-in/create-account modes, a show/hide password option and a Google-branded button. Only the assistant and WhatsApp quick contacts float on this page; the full footer was removed from login to keep attention on account access.
- Prepared production-oriented, **unconfigured** Supabase email, verified phone and Google login integrations, a staff-reviewed car-plate login alias, sign-out and password reset. Verification and real sign-in require workshop setup; see `SETUP_LOGIN.md`. The interface does not pretend a password was verified without a backend.
- Replaced the chat icon with a custom robot-style CCS mascot. Expanded the included FAQs for routine car maintenance, overheating, tyres, brakes, high-voltage safety, hybrid driving and simple everyday questions. It is not unrestricted AI.
- Simplified cinematic overlays to remove the duplicate film logo, enhance visibility of chapter controls, and retain the skip control. Correction 7 replaces chapter buttons with passive progress labels; chapters now follow scrolling only.
- Redesigned Facebook/TikTok footer cards and email contact as stronger, responsive customer calls to action.
- Tested local JS syntax, HTML links/assets, mobile menu, the password toggle, assistant answers, WhatsApp panel and mocked email/phone/plate/registration authentication request routing. Browser previews were rendered for desktop and mobile. End-to-end hosted account authentication and production video/audio were not testable without the owner’s domain and authentication provider.


## Correction 7 — 30 October 2026

Based on the supplied `correcting 7.zip` and `CCS_Solutions_Round8_Corrected_2026-10-30 (5).zip`.

- Replaced the cinematic slogan strip with a restrained CCS nameplate, location and service labels. Fixed header clearance for the sticky film and anchor entry.
- Removed all six clickable chapter controls. The bottom row is an ordered list of passive progress markers. Native wheel, touch and keyboard scrolling drive the chapters. With reduced motion enabled, the six illustrated chapters become a normal reading section, with no click navigation required.
- Rebuilt login around a single glass card. The previous story column and car video were removed; the three original supplied JPGs rotate every eight seconds with a soft crossfade. Pause/play, tab-visibility pausing and reduced-motion support are included. The existing prepared account integration is retained; it remains unconfigured, as described in `SETUP_LOGIN.md`.
- Expanded Videos to six original company posts. Added original Facebook reels `1001908876275961` and `2201145104140585`, observed on the company Reels tab. Two additional full-size Facebook adverts are included with a keyboard-accessible image viewer and original post links. The supplied 20% posters also appear in the artwork gallery. Original external players remain subject to Facebook/TikTok availability; original-post links are always offered.
- Source records: `assets/social/video-sources.json`, `assets/social/photo-sources.json`, and `assets/login/photo-sources.json`. The login photographs are decorative automotive imagery supplied by the client; they are not photographs of CCS premises. All three are included at their original 2048 × 1536 resolution.

Validation: JavaScript syntax, local page/asset references, passive chapter markup, desktop rendering, 320/390 px layouts, slideshow advancement/pause, mobile navigation, Facebook filtering, artwork lightbox, and reduced-motion chapter access. The ZIP is CRC-checked and contains every website file at its root. Extract the complete ZIP before opening `index.html`; use an HTTP(S) host for all browser features. No API keys, passwords or private account tokens are included.


## Correction 8 — 30 October 2026

Based on the supplied `correcting 8.jpg` and travel-login layout reference. The supplied `CCS_Solutions_Correcting7_Fixed(1).zip` matches the starting website byte for byte.

- The login form and photo slideshow now sit beside each other within one rounded glass panel, following the reference layout. A blue/teal background with muted gold, curved contour lines, rounded shapes and a subtle dot pattern replaces the full-page photo background. These decorations are original HTML/CSS/SVG, with no additional image dependency.
- The exact CCS logo and the three original car photographs are retained. Images stay local in `assets/login/`; they have not been retouched or upscaled. The photo pane includes previous/next controls, an eight-second automatic cycle, pause/play, progress dots and a photo counter. It pauses when the tab is hidden and respects reduced-motion preferences.
- At narrow widths the form appears first and the photo panel follows below. The fixed contact buttons use a compact layout to keep form text and slideshow controls accessible.
- Sign-in/create-account modes, identifier/password inputs, the password visibility control and the prepared Google/Supabase integration are retained. Authentication is still unconfigured in `auth-config.js`; this visual update does not activate customer accounts. Read `SETUP_LOGIN.md` for account setup.

Checked: desktop layout, 320/390/540 px widths, mobile navigation, both account modes, all three local photo assets, previous/next photo controls and pause/play. JavaScript syntax and local references are checked before packaging. The downloadable ZIP includes the complete site; extract all files together and open `index.html` or `login.html`.


## Correction 9 — 31 October 2026

Recreated the customer login to match the supplied black-and-gold reference: a dark glass form, illuminated gold frame, curved patterned ribbons and matching decorative photo edges. The existing three original client JPGs remain in the adjacent slideshow at their original resolution, with previous/next, pause/play, visibility pausing and reduced-motion support. The layout stacks cleanly on phones. Only login presentation and its documentation changed; authentication remains prepared but unconfigured, as detailed in `SETUP_LOGIN.md`.


## Correction 10 — 31 October 2026

- Removed the login photo badge, slide dots/counter, previous/next arrows and pause button requested in `correcting 10.zip`. All three original photographs still crossfade automatically. Rotation pauses while the page is hidden, motion is reduced, the photo pane is hovered or the form has keyboard focus. Authentication code and setup requirements are unchanged.
- Added an explicit EN / සිංහල switch to every page header. English remains the default; choosing Sinhala opens `sinhala.html`, a dedicated service and contact page. The existing catalogue, account area and other English pages remain available in English. English links work without JavaScript; no forced language redirects are used.
- Created the matching black/gold Sinhala page with services, parts enquiries, a three-step workshop workflow, FAQs, phone, email, Maps and a Sinhala WhatsApp draft. No message is automatically sent and no appointment is automatically booked.
- Bundled Google Fonts Abhaya Libre and Noto Sans Sinhala for consistent Sinhala rendering, including their SIL Open Font Licenses and source records in `assets/sinhala-fonts/`. Existing repair photographs are reused with their existing source attributions.


## Correction 11 — 1 November 2026

- Optimized only the mobile cinematic execution path (up to 700 px) while preserving the two sections' layout, copy, overlays, six chapters and timing. The desktop intro and film files, and existing cinematic styles, are unchanged. Mobile scrolling uses cached geometry, skips off-screen workflow measurements, updates compositor-scaled progress bars and keeps one video seek in flight with the latest target. Pending work stops when the film is off-screen, the page is hidden or motion is reduced.
- Added a 1280 × 720 mobile intro: 1,065,017 bytes instead of the 3,674,181-byte desktop file, with the same 213 frames. Re-encoded the 30-second, 720-frame mobile film at the same resolution and frame rate with quarter-second keyframes, no B frames and an early MP4 index for seeking. SSIM is 0.9941 against the previous mobile film; the new intro is 0.9966 against the downscaled original. The combined mobile video bytes are slightly lower than before. Encoding/source notes are in `assets/experience/mobile-optimization.json`.
- Rebuilt the owner section to show both co-owners, Charitha Jayadeep Duwage and Chathuri Dinushika Withanaarachchi, with matched portrait frames and captions. The new supplied PNG is included without retouching. Updated owner references and the local assistant's company answer consistently.
- Validation: nine seek-scheduler regression scenarios passed; normal mobile intro playback and automatic advance passed; all six cinematic chapters and the reduced-motion reading layout were verified. Layouts were checked at 320, 390 and 768 px plus desktop. The mobile browser walk-through recorded 144 geometry measurements in the original path and none in the corrected path. This checks browser behavior and work reduction, not physical-phone frame rates. Other pages, login configuration, logo, existing portraits and desktop cinematic assets are preserved.
