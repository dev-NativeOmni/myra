#!/usr/bin/env bash
#
# Daily backup of the SQLite database and uploaded files (logo, stamp, signature).
# Keeps 14 days locally; also copies to the rclone remote "backup:" when one is configured
# (for example a Cloudflare R2 bucket). Installed as a cron job by server-setup.sh.
#
set -euo pipefail

APP_DIR=/var/www/taqreer
BACKUP_DIR="$APP_DIR/shared/backups/daily"
STAMP="$(date +%Y%m%d-%H%M)"
KEEP_DAYS=14

mkdir -p "$BACKUP_DIR"

sqlite3 "$APP_DIR/shared/database/database.sqlite" ".backup '$BACKUP_DIR/database-$STAMP.sqlite'"
gzip "$BACKUP_DIR/database-$STAMP.sqlite"
tar -czf "$BACKUP_DIR/uploads-$STAMP.tar.gz" -C "$APP_DIR/shared/storage/app" public

find "$BACKUP_DIR" -type f -mtime +"$KEEP_DAYS" -delete

if command -v rclone >/dev/null && rclone listremotes | grep -qx 'backup:'; then
    rclone copy "$BACKUP_DIR" backup:taqreer-backups --max-age 25h
    rclone delete backup:taqreer-backups --min-age "${KEEP_DAYS}d"
fi
