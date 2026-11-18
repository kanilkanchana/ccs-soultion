# CCS Website Catalogue + Laravel Login Update v110

This is an overlay update, not a replacement of the entire website archive.
It merges the supplied home/model directory design into the existing admin-fed
website and restores the supplied login layout using existing Laravel auth.
The exact same 19 update files are used on .store, .lk and GitHub.
Domain-specific .env, database files and deployment entry points remain separate.
This does not assert that every pre-existing file on both deployments is identical.

## Included changes

- Updated home, model directory and inspection picker from the supplied ZIP.
- 194 source vehicle families. Original v97 seed: 146 additions and 48 enriched
  existing families. Two other original model rows remain; no row is deleted.
- Idempotent catalogue import into ccs_website_content only, not customers'
  vehicles, inventory, invoices or repair jobs.
- Existing model core fields, images, disabled states and owner edits retained.
  Missing variant/source/photo metadata is added to matching existing rows.
- Live admin catalogue is authoritative, including its disabled/empty states.
- Admin image overrides, load-more, make/search filters and model selection.
- Source records without a photo keep an honest 'Photo unavailable' state.
- Existing advertising, offers, video sync and inspection intake integrations
  retained. Existing integrated welcome.js/cms-render.js/round6.js are not replaced.
- Login uses supplied artwork/layout with existing Laravel username/password,
  CSRF and Email OTP routes. OTP appears only when the existing mail setup is ready.
  No Supabase account store is introduced. Google login remains unconfigured;
  Create account is replaced by Request access rather than granting new access.
- Runtime domain selection keeps STORE requests on STORE and LK on LK.
- Sinhala v109 and unrelated pages/assets are not replaced by older ZIP copies.

## Install on both domains (SSH)

Upload these 3 files into:
/home/u188760634/domains/ccssolutions.store/ccs-admin-app/

    install-catalogue-v110.php
    install-both-v110.sh
    backup-both-sites-v110.sh

In SSH run:

    cd ~/domains/ccssolutions.store/ccs-admin-app
    bash install-both-v110.sh

The shell helper first verifies both domains' original file hashes, copies the
same self-contained installer to LK, runs each site's database/route preflight,
then installs each site in its own PHP process. proc_open is not required.
Both applications must already have their own working .env and SQLite path.
A successful STORE installation is not automatically undone if LK later fails.
Each individual site's failed installation rolls back its own files/catalogue.
A rerun is safe/idempotent after resolving the reported issue.

If you want to inspect STORE first without applying changes:

    /opt/alt/php84/usr/bin/php install-catalogue-v110.php --check

For STORE alone, omit --check to install. Run the same installer in LK later.

The installer checks original hashes against the inspected GitHub commit
87afae580b9aaea27dc8091d03aa6c515f75d7f1. Domain-only STORE/LK text differences
are normalized for this check. If another update changed a touched file, STOP
and send the message for a proper merge. There is no force overwrite flag.

Before changes, each site gets a full SQLite snapshot and copies of all touched
files in storage/app/ccs-code-backups/v110-*. No migrations are run. Blade views
are compiled before committing catalogue changes, then compiled views are cleared.
Do not manually copy the update folder onto the live site: use the installer so
preflight, catalogue import and rollback protections run.

## Live checks after installation

1. Ctrl+F5 on STORE and LK index.html and models.html.
2. Search a new model (e.g. BMW 550e); test make filters and Show more.
3. Admin -> Website Content -> Car Models: check added entries. Edit a test
   model and confirm its title/photo on the website. Temporarily disable the
   test entry and confirm it disappears; restore the intended status.
4. Request an inspection using a new model. It must produce a request reference
   and appear in the SAME domain's admin inbox. This is a real test booking;
   clearly label it TEST and remove/archive it afterward using existing tools.
5. Open login.html. It should redirect to that domain's admin customer login
   and show the restored form/layout. Test an existing customer account.
6. Confirm ads/offers/videos still come from existing admin content.
7. Check mobile widths and scrolling on real devices.
8. LK requires working DNS/SSL and admin.ccssolutions.lk mapped to
   domains/ccssolutions.lk/public_html/admin. Those hosting settings are not
   configured by this installer.

## Full exact file backup to GitHub after the live checks

Run in SSH:

    cd ~/domains/ccssolutions.store/ccs-admin-app
    bash backup-both-sites-v110.sh

The helper copies BOTH complete domain directories, including .env, uploads,
dependencies and storage. Each active SQLite file is replaced in the copied
snapshot with a consistent SQLite VACUUM INTO backup; obsolete active journal
sidecars are omitted from that copied snapshot. Nothing is excluded from the
source domain trees except that database-sidecar adjustment.
Files are copied while sites run, so this is not an atomic snapshot across DB
and uploads; do it while staff are not editing files or processing sales.

It asks for a NEW encryption password, verifies decryption and archive integrity,
splits the encrypted backup into 45 MiB chunks, and uploads a separate timestamped
full-backup-v110-* branch to the same private GitHub repository. The main code
branch is not replaced. At GitHub password prompt use the repository-scoped PAT.
Keep the encryption password safely: without it the full backup cannot be restored.
Never paste the password/PAT into chat. No plaintext environment/database is
committed to GitHub. GPG must be installed on the hosting server.

DNS, SSL, hPanel settings and external cron configuration need separate records.
The full archive contains secrets/customer data. Keep local archives private.

## Validation performed

- PHP installer/importer/controller syntax checks (PHP WASM 8.5; server is PHP 8.4).
- Real Laravel/SQLite tests: dry-run, 146 insertions/48 enrichments, repeated import,
  disabled/title/image preservation, no changes to ads/videos/offers/customer data,
  expanded type validation and photo-less admin edits.
- Real Blade render tests for password, Email OTP request and OTP code modes,
  CSRF fields and domain-correct asset URLs on both domains.
- Installer fixture tests: both domains, idempotency, consistent DB backups,
  equal SHA256 update files, .env preservation, simulated compilation failure
  restoring files and rolling back catalogue rows.
- DOM tests on both domains: batching, load-more, admin image overrides,
  disabled models, booking selection, one API POST to the correct domain,
  server-confirmed reference display and login password toggle.
- Referenced local HTML resources exist in the inspected baseline/update.
- Shell helpers pass bash syntax checks.

A native visual browser could not launch in this workspace. Responsive visual
checks and live end-to-end checks remain for the deployed sites. No live site
or live customer database was changed from this workspace.
