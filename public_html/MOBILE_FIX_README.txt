CCS SOLUTIONS v3.2.5 — MOBILE AUTOPLAY CORRECTION
===================================================

REQUEST IMPLEMENTED
The film titled YOUR HYBRID. BACK AT ITS BEST. is now automatic on phone-size
screens (CSS width <= 700px) ONLY. This is the six-chapter white-car film,
not the separate welcome/intro clip or any other video.

ON PHONES
- The video plays automatically, muted, inline, once the film area enters view.
- Scroll does not scrub or repeatedly seek the video (reduces mobile freezing).
- The long sticky scroll track was replaced by a natural-height film section.
- Chapter captions and timeline follow real playback time.
- Video pauses outside the viewport, resumes on return, and loops if left in view.
- If the device or browser blocks autoplay (e.g. Low Power Mode), a Play video
  button appears; this is an unavoidable browser restriction.
- The 30-second mobile film is now 960x540 H.264 faststart and only 1.83 MB,
  compared with 8.08 MB previously (~77.4% smaller).
- Mobile-only loading-screen duration is reduced, with the existing safeguard.

ON DESKTOP / PC
- Desktop scroll-driven film controls, full desktop video quality, and layout
  are left unchanged. All other pages and site content are preserved.

HOW TO DEPLOY ON HOSTINGER
1. Back up the existing public_html directory.
2. Upload the entire ZIP and extract its CONTENTS directly into public_html
   (not into a second nested folder), replacing matching existing files.
3. Make sure the new files cinematic.js and mobile-autoplay.css are present
   next to index.html, and assets/experience/film-mobile.mp4 was replaced.
4. Clear Hostinger / LiteSpeed caching (if enabled) and do a hard refresh.
5. Test the home page on an actual phone and on a PC.

VALIDATION
- JavaScript syntax validated with Node.js.
- Mobile simulation: autoplay, caption timeline, pause offscreen, no seeks: PASS.
- Desktop simulation: no autoplay, scroll seeks retained: PASS.
- Optimized video metadata checked with ffprobe (H.264, 960x540, 24 fps, 30s).
- Live physical-device validation was not possible in the editing environment.
