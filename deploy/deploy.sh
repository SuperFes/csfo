#!/usr/bin/env bash
# Pull, build, and activate the latest main. Run as the app user:
#   sudo -u csfo bash /srv/csfo/deploy/deploy.sh
set -euo pipefail

cd /srv/csfo

git fetch --quiet origin main
git reset --hard --quiet origin/main

composer install --no-dev --optimize-autoloader --no-interaction --quiet
npm ci --no-audit --no-fund --silent
npm run build --silent

php artisan migrate --force
php artisan content:sync
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Opcache doesn't revalidate files in production; reload to pick up new code.
sudo /usr/bin/systemctl reload php8.5-fpm

# A failed purge shouldn't fail the deploy; pages expire on their own.
php artisan cdn:purge || echo "CDN purge failed; purge manually in Cloudflare."

echo "Deployed $(git rev-parse --short HEAD)"
