#!/usr/bin/env bash
set -euo pipefail
CCS_PHP=/opt/alt/php84/usr/bin/php
CCS_STORE="$HOME/domains/ccssolutions.store/ccs-admin-app"
CCS_LK="$HOME/domains/ccssolutions.lk/ccs-admin-app"
CCS_INSTALLER="$CCS_STORE/install-catalogue-v110.php"
[ -f "$CCS_INSTALLER" ] || { echo 'Upload install-catalogue-v110.php into the .store ccs-admin-app first.'; exit 1; }
"$CCS_PHP" "$CCS_INSTALLER" --both --check
if [ -f "$CCS_LK/install-catalogue-v110.php" ]; then
    cmp "$CCS_INSTALLER" "$CCS_LK/install-catalogue-v110.php"
else
    cp "$CCS_INSTALLER" "$CCS_LK/install-catalogue-v110.php"
fi
(cd "$CCS_STORE"; "$CCS_PHP" install-catalogue-v110.php --check)
(cd "$CCS_LK"; "$CCS_PHP" install-catalogue-v110.php --check)
(cd "$CCS_STORE"; "$CCS_PHP" install-catalogue-v110.php)
(cd "$CCS_LK"; "$CCS_PHP" install-catalogue-v110.php)
echo 'BOTH DOMAINS UPDATED. Each domain verified against the same update SHA256 manifest.'
echo 'Test both sites before creating the encrypted full backup.'
