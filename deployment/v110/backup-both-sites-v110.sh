#!/usr/bin/env bash
set -euo pipefail
umask 077
command -v gpg >/dev/null || { echo 'STOP: gpg is required for the encrypted backup.'; exit 1; }
command -v git >/dev/null
CCS_PHP=/opt/alt/php84/usr/bin/php
CCS_STAMP=$(date +%Y%m%d-%H%M%S)
CCS_BACKUP="$HOME/ccs-full-v110-$CCS_STAMP"
mkdir -p "$CCS_BACKUP/snapshot" "$CCS_BACKUP/repo"
for CCS_DOMAIN in ccssolutions.store ccssolutions.lk; do
    echo "Copying complete $CCS_DOMAIN folder..."
    cp -a "$HOME/domains/$CCS_DOMAIN" "$CCS_BACKUP/snapshot/"
    "$CCS_PHP" -r '
    $pdo = new PDO("sqlite:".$argv[1]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA busy_timeout=15000");
    $pdo->exec("VACUUM INTO ".$pdo->quote($argv[2]));
    ' "$HOME/domains/$CCS_DOMAIN/ccs-admin-app/database/database.sqlite" "$CCS_BACKUP/$CCS_DOMAIN.sqlite"
    CCS_DB="$CCS_BACKUP/snapshot/$CCS_DOMAIN/ccs-admin-app/database/database.sqlite"
    cp "$CCS_BACKUP/$CCS_DOMAIN.sqlite" "$CCS_DB"
    rm -f "$CCS_DB-wal" "$CCS_DB-shm" "$CCS_DB-journal"
done
tar -czf "$CCS_BACKUP/sites.tar.gz" -C "$CCS_BACKUP/snapshot" ccssolutions.store ccssolutions.lk
echo 'Choose a backup encryption password. Keep it safely; do not send it in chat.'
gpg --pinentry-mode loopback --symmetric --cipher-algo AES256 --output "$CCS_BACKUP/sites.tar.gz.gpg" "$CCS_BACKUP/sites.tar.gz"
echo 'Re-enter the same password to verify the archive.'
gpg --pinentry-mode loopback --output "$CCS_BACKUP/verified.tar.gz" --decrypt "$CCS_BACKUP/sites.tar.gz.gpg"
cmp "$CCS_BACKUP/sites.tar.gz" "$CCS_BACKUP/verified.tar.gz"
tar -tzf "$CCS_BACKUP/verified.tar.gz" >/dev/null
split -b 45M -d -a 3 "$CCS_BACKUP/sites.tar.gz.gpg" "$CCS_BACKUP/repo/sites.tar.gz.gpg.part-"
cd "$CCS_BACKUP/repo"
sha256sum sites.tar.gz.gpg.part-* > SHA256SUMS
cat > README.md <<'EOF'
# CCS full filesystem backup v110

Encrypted complete domain folders: ccssolutions.store and ccssolutions.lk.
Includes app, website, environment secrets, uploads, dependencies, storage
and consistent snapshots of each active SQLite database. Files were copied
while sites were running, so there is no atomic snapshot across files and DB.
DNS, SSL, hPanel settings and external cron configuration are not included.

Restore privately on Linux (the encryption password is required):

    sha256sum -c SHA256SUMS
    cat sites.tar.gz.gpg.part-* > sites.tar.gz.gpg
    gpg --output sites.tar.gz --decrypt sites.tar.gz.gpg
    mkdir restored
    tar -xzf sites.tar.gz -C restored

Inspect the extracted folders before replacing a live deployment.
EOF
git init -b "full-backup-v110-$CCS_STAMP"
git config user.name 'Kanil Kanchana'
git config user.email '54098000+kanilkanchana@users.noreply.github.com'
git remote add origin https://github.com/kanilkanchana/ccs-soultion.git
git add .
git commit -m 'Encrypted full backup of STORE and LK after v110'
git -c credential.helper= push -u origin "full-backup-v110-$CCS_STAMP"
echo "FULL BACKUP UPLOADED: full-backup-v110-$CCS_STAMP"
echo "Local private backup: $CCS_BACKUP"
