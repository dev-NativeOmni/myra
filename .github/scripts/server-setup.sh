#!/usr/bin/env bash
#
# One-time setup for a fresh Ubuntu 24.04 server (e.g. Oracle Cloud Always Free, ARM or x86).
# Installs PHP, Caddy (automatic HTTPS), the deploy user, app directories, cron jobs and
# automatic security updates.
#
# Usage (as root): bash server-setup.sh <domain> "<deploy public key>"
#
set -euo pipefail

DOMAIN="${1:?domain required, e.g. rapor.example.sch.id}"
DEPLOY_PUBLIC_KEY="${2:?public SSH key for GitHub Actions required}"
PHP_VERSION=8.5
APP_DIR=/var/www/taqreer
DEPLOY_USER=deploy

if [ "$(id -u)" -ne 0 ]; then
    echo "Run as root (sudo bash server-setup.sh ...)" >&2
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive

echo "==> System packages"
apt-get update
apt-get -y upgrade
apt-get install -y software-properties-common curl unzip sqlite3 acl rclone \
    unattended-upgrades debian-keyring debian-archive-keyring apt-transport-https gnupg

echo "==> Automatic security updates"
dpkg-reconfigure -f noninteractive unattended-upgrades

echo "==> Swap (only on small machines)"
if [ "$(free -m | awk '/^Mem:/{print $2}')" -lt 2048 ] && [ ! -f /swapfile ]; then
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

echo "==> PHP $PHP_VERSION"
add-apt-repository -y ppa:ondrej/php
apt-get update
apt-get install -y "php$PHP_VERSION-fpm" "php$PHP_VERSION-cli" "php$PHP_VERSION-sqlite3" \
    "php$PHP_VERSION-mbstring" "php$PHP_VERSION-xml" "php$PHP_VERSION-curl" "php$PHP_VERSION-zip" \
    "php$PHP_VERSION-gd" "php$PHP_VERSION-intl" "php$PHP_VERSION-bcmath"

echo "==> Caddy"
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | gpg --dearmor --yes -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' > /etc/apt/sources.list.d/caddy-stable.list
apt-get update
apt-get install -y caddy

echo "==> Deploy user"
id "$DEPLOY_USER" >/dev/null 2>&1 || adduser --disabled-password --gecos "" "$DEPLOY_USER"
install -d -m 700 -o "$DEPLOY_USER" -g "$DEPLOY_USER" "/home/$DEPLOY_USER/.ssh"
echo "$DEPLOY_PUBLIC_KEY" > "/home/$DEPLOY_USER/.ssh/authorized_keys"
chown "$DEPLOY_USER:$DEPLOY_USER" "/home/$DEPLOY_USER/.ssh/authorized_keys"
chmod 600 "/home/$DEPLOY_USER/.ssh/authorized_keys"
echo "$DEPLOY_USER ALL=(root) NOPASSWD: /usr/bin/systemctl reload php$PHP_VERSION-fpm" > /etc/sudoers.d/taqreer-deploy
chmod 440 /etc/sudoers.d/taqreer-deploy

echo "==> App directories"
install -d -o "$DEPLOY_USER" -g "$DEPLOY_USER" "$APP_DIR" "$APP_DIR/releases" "$APP_DIR/shared" \
    "$APP_DIR/shared/database" "$APP_DIR/shared/backups" \
    "$APP_DIR/shared/storage/app/public" "$APP_DIR/shared/storage/app/private" \
    "$APP_DIR/shared/storage/framework/cache/data" "$APP_DIR/shared/storage/framework/sessions" \
    "$APP_DIR/shared/storage/framework/views" "$APP_DIR/shared/storage/logs"
sudo -u "$DEPLOY_USER" touch "$APP_DIR/shared/database/database.sqlite"

if [ ! -f "$APP_DIR/shared/.env" ]; then
    cat > "$APP_DIR/shared/.env" <<EOF
APP_NAME=Taqreer
APP_ENV=production
APP_KEY=base64:$(openssl rand -base64 32)
APP_DEBUG=false
APP_URL=https://$DOMAIN
APP_LOCALE=id
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning

DB_CONNECTION=sqlite
DB_DATABASE=$APP_DIR/shared/database/database.sqlite

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
MAIL_MAILER=log
EOF
    chown "$DEPLOY_USER:$DEPLOY_USER" "$APP_DIR/shared/.env"
    chmod 600 "$APP_DIR/shared/.env"
fi

echo "==> PHP-FPM pool"
cat > "/etc/php/$PHP_VERSION/fpm/pool.d/taqreer.conf" <<EOF
[taqreer]
user = $DEPLOY_USER
group = $DEPLOY_USER
listen = /run/php/taqreer.sock
listen.owner = caddy
listen.group = caddy
pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 2
pm.max_spare_servers = 4
php_admin_value[memory_limit] = 512M
php_admin_value[max_execution_time] = 120
php_admin_value[upload_max_filesize] = 10M
php_admin_value[post_max_size] = 12M
EOF
systemctl restart "php$PHP_VERSION-fpm"

echo "==> Caddy site"
cat > /etc/caddy/Caddyfile <<EOF
$DOMAIN {
    root * $APP_DIR/current/public
    encode zstd gzip
    php_fastcgi unix//run/php/taqreer.sock
    file_server
    header {
        -Server
        X-Content-Type-Options nosniff
        X-Frame-Options SAMEORIGIN
        Referrer-Policy strict-origin-when-cross-origin
    }
}
EOF
systemctl reload caddy

echo "==> Firewall (Oracle images block 80/443 in iptables by default)"
if command -v netfilter-persistent >/dev/null; then
    for port in 443 80; do
        iptables -C INPUT -p tcp --dport "$port" -m state --state NEW -j ACCEPT 2>/dev/null \
            || iptables -I INPUT 5 -p tcp --dport "$port" -m state --state NEW -j ACCEPT
    done
    netfilter-persistent save
fi

echo "==> Cron: scheduler every minute, backup daily at 02:00"
cp "$(dirname "$0")/backup.sh" /usr/local/bin/taqreer-backup
chmod 755 /usr/local/bin/taqreer-backup
cat > /etc/cron.d/taqreer <<EOF
* * * * * $DEPLOY_USER cd $APP_DIR/current && php artisan schedule:run >> /dev/null 2>&1
0 2 * * * $DEPLOY_USER /usr/local/bin/taqreer-backup >> $APP_DIR/shared/storage/logs/backup.log 2>&1
EOF

echo
echo "Server siap. Langkah berikutnya:"
echo "  1. Pastikan DNS $DOMAIN mengarah ke IP server ini."
echo "  2. Di GitHub: isi secrets SSH_HOST, SSH_USER=$DEPLOY_USER, SSH_PRIVATE_KEY, SSH_KNOWN_HOSTS,"
echo "     lalu buat repository variable DEPLOY_ENABLED=true dan jalankan workflow 'Test & Deploy'."
echo "  3. Setelah deploy pertama berhasil, buat akun admin:"
echo "     sudo -u $DEPLOY_USER php $APP_DIR/current/artisan app:create-super-admin <username>"
