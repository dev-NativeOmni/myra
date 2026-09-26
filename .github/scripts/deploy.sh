#!/usr/bin/env bash
#
# Activates a release uploaded by GitHub Actions. Runs on the server as the deploy user.
# Usage: deploy.sh <release.tar.gz> <short-sha>
#
set -euo pipefail

TARBALL="${1:?release tarball required}"
SHA="${2:?commit sha required}"
APP_DIR=/var/www/taqreer
PHP_VERSION=8.5
KEEP_RELEASES=5

RELEASE="$(date +%Y%m%d%H%M%S)-$SHA"
RELEASE_DIR="$APP_DIR/releases/$RELEASE"
PREVIOUS_DIR="$(readlink -f "$APP_DIR/current" 2>/dev/null || true)"
DB_FILE="$APP_DIR/shared/database/database.sqlite"
APP_HOST="$(grep -E '^APP_URL=' "$APP_DIR/shared/.env" | cut -d= -f2- | sed -E 's#^https?://##; s#/.*$##')"

echo "==> Unpacking release $RELEASE"
mkdir -p "$RELEASE_DIR"
tar -xzf "$TARBALL" -C "$RELEASE_DIR"
rm -f "$TARBALL"

echo "==> Linking shared files"
rm -rf "$RELEASE_DIR/storage"
ln -s "$APP_DIR/shared/storage" "$RELEASE_DIR/storage"
ln -s "$APP_DIR/shared/.env" "$RELEASE_DIR/.env"

cd "$RELEASE_DIR"
php artisan storage:link --force --no-interaction

echo "==> Backing up database before migrating"
mkdir -p "$APP_DIR/shared/backups"
sqlite3 "$DB_FILE" ".backup '$APP_DIR/shared/backups/pre-deploy-$RELEASE.sqlite'"
gzip "$APP_DIR/shared/backups/pre-deploy-$RELEASE.sqlite"

php artisan migrate --force --no-interaction
php artisan optimize --no-interaction

echo "==> Switching to new release"
ln -sfn "$RELEASE_DIR" "$APP_DIR/current.tmp"
mv -Tf "$APP_DIR/current.tmp" "$APP_DIR/current"
sudo systemctl reload "php$PHP_VERSION-fpm"

echo "==> Health check https://$APP_HOST/up"
if ! curl -fsS --max-time 15 --retry 3 --retry-delay 2 --retry-all-errors \
    --resolve "$APP_HOST:443:127.0.0.1" -o /dev/null "https://$APP_HOST/up"; then
    echo "!! Health check failed, rolling back code to previous release"
    if [ -n "$PREVIOUS_DIR" ] && [ -d "$PREVIOUS_DIR" ]; then
        ln -sfn "$PREVIOUS_DIR" "$APP_DIR/current.tmp"
        mv -Tf "$APP_DIR/current.tmp" "$APP_DIR/current"
        sudo systemctl reload "php$PHP_VERSION-fpm"
    fi
    echo "!! Database backup from before this deploy: shared/backups/pre-deploy-$RELEASE.sqlite.gz"
    exit 1
fi

echo "==> Cleaning up old releases and pre-deploy backups"
ls -1dt "$APP_DIR"/releases/*/ | tail -n +$((KEEP_RELEASES + 1)) | xargs -r rm -rf
ls -1t "$APP_DIR"/shared/backups/pre-deploy-*.sqlite.gz 2>/dev/null | tail -n +$((KEEP_RELEASES + 1)) | xargs -r rm -f

echo "==> Deployed $RELEASE"
