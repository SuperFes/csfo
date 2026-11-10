#!/usr/bin/env bash
# One-time (idempotent) server setup for Debian 13 on a 1 GB e2-micro.
# Run as root: sudo bash deploy/provision.sh
set -euo pipefail

APP_USER=csfo
APP_DIR=/srv/csfo
REPO=https://github.com/SuperFes/csfo.git
PHP=8.5
NODE_MAJOR=24

export DEBIAN_FRONTEND=noninteractive

apt-get update -q
apt-get install -yq ca-certificates curl gnupg git unzip sqlite3 nginx lsb-release

# 1 GB of RAM is not enough for composer + vite builds without swap.
if ! swapon --show | grep -q /swapfile; then
    fallocate -l 1G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

install -d -m 0755 /etc/apt/keyrings
codename=$(lsb_release -sc)

if [ ! -f /etc/apt/sources.list.d/php.list ]; then
    curl -fsSL https://packages.sury.org/php/apt.gpg -o /etc/apt/keyrings/sury-php.gpg
    echo "deb [signed-by=/etc/apt/keyrings/sury-php.gpg] https://packages.sury.org/php/ ${codename} main" > /etc/apt/sources.list.d/php.list
fi

if [ ! -f /etc/apt/sources.list.d/nodesource.list ]; then
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_${NODE_MAJOR}.x nodistro main" > /etc/apt/sources.list.d/nodesource.list
fi

if [ ! -f /etc/apt/sources.list.d/cloudflared.list ]; then
    curl -fsSL https://pkg.cloudflare.com/cloudflare-main.gpg -o /etc/apt/keyrings/cloudflare-main.gpg
    echo "deb [signed-by=/etc/apt/keyrings/cloudflare-main.gpg] https://pkg.cloudflare.com/cloudflared any main" > /etc/apt/sources.list.d/cloudflared.list
fi

apt-get update -q
apt-get install -yq nodejs cloudflared \
    php${PHP}-fpm php${PHP}-cli php${PHP}-sqlite3 php${PHP}-mbstring php${PHP}-xml \
    php${PHP}-curl php${PHP}-intl php${PHP}-zip php${PHP}-bcmath

if ! command -v composer >/dev/null; then
    expected=$(curl -fsSL https://composer.github.io/installer.sig)
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    actual=$(sha384sum /tmp/composer-setup.php | cut -d' ' -f1)
    [ "$expected" = "$actual" ] || { echo 'Composer installer checksum mismatch' >&2; exit 1; }
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --quiet
    rm /tmp/composer-setup.php
fi

id -u "$APP_USER" >/dev/null 2>&1 || useradd --system --create-home --home-dir /home/$APP_USER --shell /bin/bash "$APP_USER"
usermod -aG "$APP_USER" www-data

if [ ! -d "$APP_DIR/.git" ]; then
    install -d -o "$APP_USER" -g "$APP_USER" "$APP_DIR"
    sudo -u "$APP_USER" git clone "$REPO" "$APP_DIR"
fi

if [ ! -f "$APP_DIR/.env" ]; then
    sudo -u "$APP_USER" cp "$APP_DIR/deploy/env.production" "$APP_DIR/.env"
    sudo -u "$APP_USER" touch "$APP_DIR/database/database.sqlite"
    (cd "$APP_DIR" && sudo -u "$APP_USER" composer install --no-dev --no-interaction --quiet && sudo -u "$APP_USER" php artisan key:generate --force)
fi

install -m 0644 "$APP_DIR/deploy/php-fpm-pool.conf" /etc/php/${PHP}/fpm/pool.d/csfo.conf
rm -f /etc/php/${PHP}/fpm/pool.d/www.conf
install -m 0644 "$APP_DIR/deploy/php-opcache.ini" /etc/php/${PHP}/fpm/conf.d/90-csfo-opcache.ini
install -m 0644 "$APP_DIR/deploy/nginx.conf" /etc/nginx/sites-available/csfo
ln -sf /etc/nginx/sites-available/csfo /etc/nginx/sites-enabled/csfo
rm -f /etc/nginx/sites-enabled/default

# Let the app user reload PHP after a deploy without full sudo.
echo "$APP_USER ALL=(root) NOPASSWD: /usr/bin/systemctl reload php${PHP}-fpm" > /etc/sudoers.d/csfo
chmod 0440 /etc/sudoers.d/csfo

nginx -t
systemctl enable --now php${PHP}-fpm nginx
systemctl reload php${PHP}-fpm nginx

echo "Provisioned. Next: sudo -u $APP_USER bash $APP_DIR/deploy/deploy.sh"
