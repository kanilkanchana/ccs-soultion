# CCS v93 — Compact Warranty / Ledger workspaces (single-file installer)
Requires existing v90-v92 app. This update addresses marked Warranty and Credit Ledger screens only.

## Easy live install
This ZIP has ONE installer and this README. Do not copy app/resources folders.
1. Upload/extract install-ui-v93.php into:
   /home/u188760634/domains/ccssolutions.store/public_html/
2. SSH run:
```bash
/opt/alt/php84/usr/bin/php ~/domains/ccssolutions.store/public_html/install-ui-v93.php
```
3. Check output: correct Laravel root, Updated layouts:2, public admin assets copied, v93 installed.
4. Ctrl+F5 once. Open Warranty and Credit Ledger again.

Installer automatically finds sibling ccs-admin-app, backs up the actual active HTML layouts, injects new CSS/JS links, writes assets and copies them to public_html/admin/ccs-ui when admin/index.php exists. No manual asset copy needed in your confirmed Hostinger layout. It also works from the private Laravel root using php install-ui-v93.php.
If an error appears, stop and send the output. No proc_open, migrations, env/key/vendor changes or business record updates.

## Marked UI areas
- Magenta: browser/main content viewport locked; no whole-page scroll.
- Red: Warranty card list or Credit Ledger table independently scrolls. Long right details have their own bounded scroll so nothing is lost.
- Blue: Input Method/System Language use separate icon menus on Desktop too; Warranty toolbar actions become compact icon buttons with accessible labels/tooltips. Original forms, CSRF and handlers retained.
- Yellow: navigation shortcuts, page title/header, filters and margins compacted. Warranty Find Customer/Select Customer/Filter become one compact row on wide screens. Desktop sidebar modestly narrowed on wide displays.
- Record panels fill remaining viewport height after compact headers. Narrow displays stack within a bounded record workspace. Other pages remain unchanged. Print CSS not affected.

## Verification and limits
Browser dummy fixtures using the original record-selection scripts: Warranty and Ledger main viewport scrollHeight equals viewport clientHeight; main content overflow hidden; lists/tables overflow auto and contain extra records. Compact toolbar40px; expanded Warranty record region676px at tested936px viewport height. Icon language menu visibly opens with original selector. Real production records are not modified.
Installer integration fixture: discovers app from public_html, makes private code backups, validates Blade/PHP parse, injects assets idempotently across reruns, copies public assets. Installer PHP/JS syntax pass.
Real live deployment/device performance has not been directly tested. If the UI still looks old, send the full installer output; it identifies which root/layouts were updated.

## Rollback
Installer prints a private backup path under storage/app/ccs-ui-code-backups/v93-... . Copy its resources folder back over the private Laravel project's resources folder, then run php artisan view:clear and Ctrl+F5. Old v92 behavior returns. New v93 assets may remain unused.
