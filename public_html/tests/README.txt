CCS Account Roles v94 — install after v90-v93

Upload ONLY install-roles-v94.php into ccs-admin-app (private Laravel root).
SSH:
cd ~/domains/ccssolutions.store/ccs-admin-app
/opt/alt/php84/usr/bin/php install-roles-v94.php

If you uploaded to public_html, run instead:
/opt/alt/php84/usr/bin/php ~/domains/ccssolutions.store/public_html/install-roles-v94.php

No asset copying required. Refresh/sign in again.
Owner: System Settings > Users & Roles, assign Owner, Staff / Cashier, or Technician.
Existing legacy/manager/staff roles act as Staff / Cashier; no bulk database rewrite.
Owner controls users/security/backups/audit and warranty/claim approvals.
Staff/Cashier has workshop, stock, billing, payments, credit, customer and warranty operations.
Technician gets My/Current Jobs, available-stock view, status/assignment and existing notes only.
Technician cannot access financial/customer-management endpoints, create stock/jobs or approve claims.
Both /ccs and /t routes use the same backend/data and role enforcement.
Unsupported technician workflows (photos/checklists/new parts-request workflow) are not introduced.
Existing desktop/touch layouts, themes and Appearance UI mode controls are retained.

Installer backs up changed files under storage/app/ccs-code-backups/v94-... and validates PHP/Blade before applying.
To roll back: copy backup CONTENTS into Laravel root, then run php artisan optimize:clear.
Do not replace .env, database, vendor, bootstrap or public index.php.
No migrations, schema changes or business-data edits. No live-server installation was performed here.

Validation: role allow/deny matrix; inactive/legacy accounts; SQLite own/current jobs;
financial-data exclusion; safe status/assignment/notes save; invalid assignment/archive edits;
Blade compilation; desktop/touch rendered technician navigation and layout inspection.
