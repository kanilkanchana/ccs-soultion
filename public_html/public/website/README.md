# Dashboard-connected update - 29 October 2026

Read ../JINUKA_CONNECTIVITY.md and ../INSTALL_AND_TEST_SI.md before deployment.
Forms now submit to the Laravel Website Requests inbox when the connection is configured. Original design/media retained. The following is Jinuka's original description of the pre-integration build; its WhatsApp-only forms and hosted ChatGPT sign-in notes are historical, not current behavior.

---

# CCS Solutions — Hybrid Care. Recharged.

A responsive, buildless website for CCS Solutions in Ragama, Sri Lanka. The repository serves `dist/`. The download ZIP puts all website files directly at its root: extract it and open `index.html`, or upload the extracted files to a static host. No package installation or build is needed. Keep the assets folders beside the HTML files.

## Pages and interactions

- Home: once-per-session, two-poster welcome promotion carousel, orbital startup loader, orbital startup loader, loading counter and elapsed timer, supplied seven-second introduction, six cinematic scroll chapters, photographic “From Symptom to Solution” workflow, service details, battery animations, model preview, Google review slideshow, owner section, inspection form and map.
- Car models: 50 representative hybrid, mild-hybrid and plug-in models across 12 makes, searchable by brand, name, chassis or hybrid type. Each car has a white presentation field, a compact PNG reference and an optimized WebP. Petrol-only Wagon R FA is excluded. This is an enquiry directory; confirm parts and workshop availability for the exact variant.
- Videos: four verified company workshop videos: one Facebook reel and three TikTok posts. Local previews, Facebook / TikTok filters, original-post links and an on-demand embedded player. The generic cinematic car clips stay on the homepage and are excluded from this workshop video page.
- Offers: the two exact client-supplied 20% promotional posters, with service-specific enquiry links, followed by battery, ABS and diagnostics service spotlights. The promotion eligibility and pricing are confirmed with the workshop; no expiry date is invented.
- Warranty: guidance to check the written terms for the selected work, plus a warranty enquiry form. The workshop decides coverage; the site does not invent warranty periods or approve claims.
- Contact: phone, direct WhatsApp, exact Google business pin and the full inspection request form. Model and service selections carry across from relevant pages.
- Login: a prominent header button opens a separate cinematic customer-access page, with the supplied vehicle video, dark glass panel and native Sign in with ChatGPT leading to the visit planner.
- Visit planner: useful workshop links and a vehicle note stored only in this browser. It does not show appointments, invoices or customer records.
- Sources: photography, media and business-information credits.

All pages include responsive navigation, direct company Facebook and TikTok links, a floating WhatsApp contact panel and a motion preference. Browser reduced-motion settings are also respected. The interior video pauses offscreen; the review slideshow has manual controls and an explicit pause button; it resumes its timer after manual arrows. The removed introduction replay/enter/time bar and interior pause button are not included.

## Inspection and warranty enquiries

The forms validate entries and show a message preview. Customers choose to open WhatsApp and send the message themselves. The workshop confirms an appointment, price, compatibility and any warranty directly. No message is sent automatically. There is no booking database, payment collection or server-side customer-record storage.

## Sign-in and hosting

The existing hosted Site remains private. Its native platform sign-in only admits accounts allowed by the Site owner. The login button uses `/signin-with-chatgpt?return_to=%2Fportal.html` on the hosted Site. Local or preview copies link to that same hosted sign-in page.

Sign-in is supplied by the Sites hosting platform, not by the HTML ZIP. Another static host must supply its own authentication and route protection before using the visit planner for private information. The local vehicle note is device-only and must not be treated as an account or workshop record.

## Business details and reviews

- CCS Solutions — Hybrid Battery Repair
- 318/A Maharanugegoda Road, Ragama 11850, Sri Lanka
- +94 71 793 7011
- Google Maps: https://www.google.com/maps?cid=11273773956273965881
- Facebook: https://www.facebook.com/profile.php?id=61567738112126
- TikTok: https://www.tiktok.com/@ccs.solutions1

The exact supplied Google listing labels the postal address as Ragama, although the client located it through a Kadawatha search. The map uses the business CID rather than a general location search.

Google was checked on 28 October 2026: 4.9 / 5 from 73 reviews. The slideshow uses three short, publicly verified excerpts by Malaka Nuwan, Gayan Buddika and A&C events DJ Ashley. Google required sign-in to reveal more reviews, so no additional reviews were copied or invented. This is a dated snapshot, not a live feed. Refresh the page and `assets/google-review-sources.json` together when adding an authorised export or newly verified reviews.

## Images and media

Fonts, display images and homepage cinematic/interior videos are local. The workshop video players, map, social links, sign-in and WhatsApp need an internet connection.

Vehicle images are representative model-family or trim photographs. Some pictures show one generation from a listed family; parts and chassis must be confirmed with the workshop. Compact files are at most 900px wide, with no source upscaling during export. Empty white margins were trimmed, and the original photo content was resized and padded on white. Source dimensions, credited pages and available licence information are recorded in `assets/vehicles/image-sources.json`. Lower-resolution references retain their native detail. Large vehicle masters are excluded to keep the ZIP manageable. Manufacturer marks identify makes and do not imply affiliation.

The logo, owner portrait, introduction and scroll film came from the client's supplied files. The latest client-supplied interior clip replaces the previous photograph. It is compressed locally, with a poster from the same clip, and is not claimed to show CCS premises. Charitha Jayadeep Duwage's name and owner role came from the correction brief. Identity and social provenance are in `assets/identity-sources.json`.

Service and workflow photos illustrate the work discussed. Repair examples are clearly labelled scenarios. Sources are credited on `sources.html` and in the included source records. Pexels images retain their original licence: https://www.pexels.com/license/. The hybrid battery pack image is TTTNIS's public-domain Toyota Prius PHV battery photograph from Wikimedia Commons. Badges use Simple Icons and a CC0 Lexus reference. Barlow Condensed and Manrope fonts are self-hosted.

## Latest correction details

The welcome carousel changes every 5.5 seconds while visible; manual selection, pause, close, continue and offer enquiry actions work independently. Reduced-motion and global motion-off preferences disable automatic slide changes. The review carousel advances every 6.5 seconds while in view; the explicit Pause button, motion settings and page visibility control autoplay.

The four company video previews were checked on Facebook and TikTok on 28 October 2026. Preview images are included locally; playback stays with the source platform and requires internet access. `assets/social/video-sources.json` records the exact sources. The posters were supplied by the client in the latest correction brief; their full PNG versions and compact WebP display versions are in `assets/promotions/`.

The request for at least ten Google reviews still requires further source access. Only the three public, verified reviews are included. Opening Google sign-in was blocked by automatic approval review because personal-account access had not been explicitly authorised. No extra reviewer names, ratings or comments were invented.
