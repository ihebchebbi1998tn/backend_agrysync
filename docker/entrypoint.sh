#!/bin/bash

echo "==> AgriTrack API starting"

# Render injects PORT (default 10000). Patch Apache to match.
PORT=${PORT:-80}
echo "==> Configuring Apache on port $PORT"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

echo "==> Ensuring storage directories exist"
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache

echo "==> Caching config"
php artisan config:cache || echo "[warn] config:cache failed"

echo "==> Running migrations"
php artisan migrate --force || echo "[warn] migrate failed - check DATABASE_URL is set in Render dashboard"

echo "==> Starting Apache"
exec /usr/local/bin/apache2-foreground
