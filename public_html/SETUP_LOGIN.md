# CCS Solutions — Account login setup

The login page is fully designed and interactive. **No real customer accounts or live authentication have been activated**, because the uploaded website did not include an authorised authentication service, project URL, public key or Google/SMS settings. The ZIP deliberately does **not** store customer passwords or create fake accounts in localStorage. Once a workshop-controlled Supabase project is configured, it can authenticate accounts on a secure hosted domain.

## What is included

- Email + password login and registration, forgot-password requests, and Google sign-in UI.
- Phone + password login for an **already verified phone number** linked to the same Supabase account. Enter Sri Lankan numbers in `0712345678` or `+94712345678` form.
- Vehicle registration number + password login through the included rate-limited Supabase Edge Function. Plate registration must be verified manually by CCS staff before it becomes a sign-in alias. The plate **does not replace a password**.
- A visit planner with a device-only note and, when signed in, a form to request vehicle-plate verification or phone SMS verification.
- Graceful public-site fallback when authentication isn't configured; the old platform-native invitation sign-in remains available if the site is hosted on `*.chatgpt.site`.

## Required one-time setup by the workshop

1. Create a CCS Solutions-owned [Supabase](https://supabase.com/) project. Configure a custom domain and HTTPS deployment for the static website before inviting real customers.
2. In Supabase **Authentication → Providers**, enable Email and configure its confirmation and recovery emails. Configure a production mail provider and rate limits. For Google, enable the Google provider, obtain OAuth client credentials in Google Cloud and add the Supabase callback URL to authorised redirects. Google login will not work without this setup.
3. In **Authentication → URL Configuration**, set the exact hosted Site URL and allow the login-page redirect (`https://YOUR-DOMAIN/login.html`). For security, restrict redirects to domains controlled by CCS Solutions.
4. If customers need phone sign-in, enable Phone Auth and connect a supported SMS provider. New users must add and **verify** their number in the signed-in visit planner before using phone + password. Check SMS pricing and protection against abuse.
5. Run `supabase/migrations/20261030_plate_auth.sql` in the **Supabase SQL Editor**. This creates a locked-down plate-identifiers table and a sign-in rate limit function.
6. Deploy `supabase/functions/plate-login/index.ts` to the same Supabase project as function name `plate-login`, with the function **Verify JWT disabled** at the gateway (it validates user JWTs itself for the authenticated link action; plate/password login starts without a JWT). Set server-side `CCS_SITE_ORIGINS=https://YOUR-DOMAIN` (add a comma-separated preview origin only if needed). Supabase supplies `SUPABASE_URL`, `SUPABASE_ANON_KEY` and `SUPABASE_SERVICE_ROLE_KEY` to the Edge Function. **Never add the service-role key to any website JavaScript or HTML.** Use a server-side WAF/rate limit in addition to the provided per-IP-and-plate throttle for a public site.
7. Edit `auth-config.js` (public file) with your Supabase project URL `https://YOUR-PROJECT.supabase.co` and public **anon/publishable** key. This key is not secret, but use least-privilege database policies and protect server secrets. Do not paste a service-role key there.
8. Have a customer create an account via email, confirm their email, sign in and submit their vehicle number in the visit planner. CCS staff should check evidence of registration/ownership and then approve only the appropriate record, e.g. in SQL after verifying the ID: `update public.ccs_customer_plates set verified_at=now() where plate_normalized='CAA1234' and user_id='VERIFIED-ACCOUNT-UUID';`. Do not approve unverified plate submissions.
9. Test email sign-up, incorrect passwords, password reset, Google OAuth, phone OTP, plate approval and plate denial on the **actual HTTPS domain** before accepting customers. Also test network errors, mobile layout, sign-out and rate limits.

## Security and scope

This website is static and cannot safely provide an account service by itself. Supabase owns the password check, Google OAuth and email confirmation, while the server-side function resolves verified plate aliases. Customer input is not considered a verified account until the provider confirms it. The visit planner's note is **local to that browser**, not synced to Supabase or visible to workshop employees; there is no appointment database or repair-history portal. Public pages remain accessible without login. Protect any future private customer records with server-side role permissions/RLS, not merely hidden HTML links.

The session token is currently held only in browser `sessionStorage` and cleared on tab closure/sign-out; configure HTTPS, a CSP and appropriate hosting security controls before production. For a business handling actual personal information, a production-grade session/authentication review by its developer is essential. Phone and plate logins are **prepared integrations**, not live until the steps above are completed.

## Assistant limitations

The assistant now covers 17 original CCS topics plus additional practical automotive and everyday FAQs, local clock/date display, simple arithmetic and greeting/joke responses. It remains **offline retrieval**, not a live large language model; it cannot answer every unrelated open-ended question, fetch live information or diagnose a vehicle. Its local embedding runtime is included in the ZIP and activates when served over HTTP(S); opening `index.html` via `file://` uses quick matching. Do not claim that it has real-time repair diagnostics, live prices or unrestricted AI knowledge without adding a separately secured backend service.


## Correction 8 presentation update

The sign-in form now sits to the left of a local car-photo slideshow in a light glass panel. On mobile the slideshow follows the form. This change preserves the existing account integration and its configuration requirements. The previous/next and pause/play buttons control decorative photographs only; they do not submit the sign-in form or send customer information.


## Correction 9 presentation update

The customer-access page now follows the supplied black-and-gold design reference. The glass panel, fields, controls, background ribbons and photo-edge shapes share the CCS gold branding. All three original photographs, slideshow controls and account integration are preserved. This presentation update does not activate or change authentication.


## Correction 10 presentation update

The login slideshow no longer displays the photo badge, counter, arrows or pause button, as requested. The same original photos rotate automatically, and pause for reduced motion, a hidden tab, hovering the photo pane or keyboard focus within the login panel. The language switch opens the dedicated Sinhala services page; authentication remains in English and still requires the setup above.
