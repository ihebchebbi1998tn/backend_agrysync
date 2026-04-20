#!/bin/bash

# Render injects PORT (default 10000). Configure Apache to match.
PORT=${PORT:-80}
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Ensure writable runtime dirs exist
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs
chown -R www-data:www-data storage bootstrap/cache

php artisan config:cache

# route:cache is skipped — closure routes (health, web root) cannot be serialized.
# Remove this comment and add route:cache only if all routes use controllers.

php artisan migrate --force || echo "[warn] migrate failed — DATABASE_URL may not be set"

exec apache2-foreground
