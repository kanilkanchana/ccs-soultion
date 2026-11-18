CCS POS/Keyboard/Mobile v95 (after v94; v90+ layout required)

Upload install-pos-v95.php to the PRIVATE ccs-admin-app folder, then SSH:
cd ~/domains/ccssolutions.store/ccs-admin-app
/opt/alt/php84/usr/bin/php install-pos-v95.php

If uploaded into public_html instead:
/opt/alt/php84/usr/bin/php ~/domains/ccssolutions.store/public_html/install-pos-v95.php

Installer locates your private app, validates PHP/Blade, backs up changed code,
preserves v94 roles and latest layout changes, clears caches without proc_open,
and copies the two public assets to public_html/admin/ccs-ui automatically.
Use Ctrl+F5. No manual folder merging or migrations. No database changes.
--check validates without writing. Backup path printed; manifest maps files to original locations.

Flow: enter exact item/barcode or serial > Enter. Unique match opens serial selection;
multiple ambiguous matches remain visible for selection. Up/Down/Home/End selects a unit;
Enter adds it. Sealed packs accept typed serial + Enter. Battery modules still require
position/voltage. Units already in this draft cart cannot be selected twice; removing
one immediately restores its availability. Stock is not consumed until invoice save.
Server-side duplicate/stock checks remain unchanged.

Touch keyboard: bottom sheet, outside tap/Enter dismisses. Amount keypad lives inside
checkout, does not blur/block catalog. Language icon on keyboard offers English,
Sinhala Phonetic, Sinhala Wijesekara. Sinhala labels shown for Sinhala-enabled fields;
serials/item codes/amounts intentionally stay English/numeric for scanning accuracy.
Mobile/tablet native keyboard retained; custom keypad disabled for mobile UA/coarse
small touch devices. Phone layout stacks catalog/cart; tablet uses responsive columns.
Mobile GET page navigation uses /t routes; saved desktop preference is not overwritten.
Small desktop screens <=700px also redirect to Touch layout before body render.

Tests: real Blade render; code+Enter; arrow serial selection; Enter add; remove/restore;
Phonetic 'kanil' and Wijesekara 'ld'; keyboard labels/dismissal; mobile-native suppression;
PHP/JS syntax. Browser preview checked checkout-only sheet and phone/tablet widths.
Real-device native keyboards and live-server behavior still require your device test.
