# CCS Website Content v97

Based on Jinuka's latest `ccs solutions the correct one(1).zip`.
Requires the existing CCS Laravel app and v94 account permissions (v95/v96 POS stays intact).
This package is an update, not a fresh Laravel installation.

## What it adds

Website Content in the existing admin navigation, with four tabs:
Advertisements, Car Models, Videos, Offers. Each has image cards, search, edit,
active/disable and display order. New cards and uploads are supported.
Owner and Staff/Cashier may manage content; Technician cannot.
Works under /ccs/website-content and the existing /t/website-content route alias.

## Original data preserved

2 ads, 50 car models/photos, 6 original social videos, 3 offers are seeded into
`ccs_website_content`. Names/text/media come from the supplied website, not dummy data.
Existing artwork/photos and their source/license files remain under the supplied assets tree.
`original-assets-sha256.json` records original asset bytes.
New files save under the admin public root:
website-media/ads, website-media/models, website-media/videos, website-media/offers.
Existing seed cards are NOT overwritten on a repeated installation.
No customer, invoice, stock, job, account, or POS table is replaced.
Only the new CMS migration is run, avoiding unrelated pending migrations.

## Install on Hostinger

1. Upload the ZIP into `domains/ccssolutions.store/public_html`.
2. Extract it there. Keep the package folder together; do not flatten or individually move files.
   It should create `public_html/CCS-Website-Content-v97/` containing this README,
   install-website-v97.php, laravel-update/ and website/.
3. In SSH run this check:

```bash
/opt/alt/php84/usr/bin/php ~/domains/ccssolutions.store/public_html/CCS-Website-Content-v97/install-website-v97.php --check
```

4. If the check passes, install:

```bash
/opt/alt/php84/usr/bin/php ~/domains/ccssolutions.store/public_html/CCS-Website-Content-v97/install-website-v97.php
```

The installer finds the sibling `ccs-admin-app` project, backs up changed files and
creates a consistent SQLite database snapshot, installs only the CMS migration,
imports the 61 original cards, copies public website files and clears Laravel caches.
It does not call proc_open or replace .env/vendor/public index.php.
The main website root must already contain index.html. Paths/URLs can be overridden:
`--site-root=/absolute/path --site-url=https://ccssolutions.store --admin-url=https://admin.ccssolutions.store`.
It writes only files in this package, never deletes unrelated website files.

5. Ctrl+F5, then sign in to the existing admin and open Website Content.
   Expected admin URL: https://admin.ccssolutions.store/ccs/website-content
   Public database feed: https://admin.ccssolutions.store/website-content.js
   Public website: https://ccssolutions.store/

The full original front-end is included and copied during installation. Review/install
on staging first if the live site has been edited since the supplied ZIP.

## Daily use

- Ads: image, main/highlighted title, discount/label, description, service, CTA and terms.
- Models: make, model, chassis/model code, HEV/PHEV/EV/ICE and photo.
- Videos: direct MP4/WebM upload or HTTPS URL; public Facebook/Instagram/TikTok/YouTube links.
  Select Direct video for uploads. A thumbnail is optional. No iframe HTML is accepted.
  Platform privacy, region/login rules and browser protection may prevent an embed;
  the original-video link remains available. Image limit 10 MB, video limit 100 MB,
  further limited by the hosting account's PHP upload/post settings.
- Offers: photo, title, description, optional discount, one-per-line details and CTA.
- Disable keeps the record in admin but excludes it from the public feed.
- Website pages load the feed first. If the feed fails they do not revive disabled cards.
- Concurrent edits/status changes use revision checks. A stale screen must be reloaded.

## Verification

SQLite: migration + seed rerun, original count, create/edit/status, public filtering,
role gate, stale edit conflict, URL/platform validation, image upload grouping and Blade render.
Browser: desktop cards and phone/tablet editor layout; JS tests for model search,
ads, offers, direct/Instagram player, disable-all behavior and text escaping.
No live installation was performed. Hostinger account MCP tools are not connected
in this session, so live folder access/deployment could not be used.

## Rollback

The installer prints a private `storage/app/ccs-code-backups/v97-...` directory.
`files.json` maps changed target paths to backup files (null means a newly added file).
Restore those code files if needed and clear Laravel caches. Leave the new CMS table
in place rather than rolling back unrelated migrations. Restore the SQLite snapshot
only as a deliberate full database rollback; it also reverses any later business writes.
If installation fails, code files are restored, but the new CMS table/import may remain.
Retry is safe for seeded slugs and does not overwrite later card edits.
