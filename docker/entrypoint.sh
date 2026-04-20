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

# Ensure .env exists so key:generate has a file to write to
touch .env

# Render's generateValue produces a plain random string without the required
# "base64:" prefix that Laravel needs. Detect and auto-generate a valid key.
if [ -z "$APP_KEY" ] || [ "${APP_KEY#base64:}" = "$APP_KEY" ]; then
    echo "==> APP_KEY missing or not in base64: format — generating one"
    php artisan key:generate --force
    unset APP_KEY
else
    echo "==> APP_KEY is valid"
fi

echo "==> Caching config"
php artisan config:cache || echo "[warn] config:cache failed"

# ---------------------------------------------------------------------------
# Migrations
# ---------------------------------------------------------------------------
echo ""
echo "========================================"
echo "  DATABASE MIGRATIONS"
echo "========================================"
php artisan migrate --force --verbose 2>&1
MIGRATE_EXIT=$?
if [ $MIGRATE_EXIT -eq 0 ]; then
    echo "==> [OK] Migrations completed successfully"
else
    echo "==> [FAIL] Migrations failed (exit $MIGRATE_EXIT)"
    echo "    Check that DATABASE_URL is set correctly in Render dashboard"
fi
echo "========================================"
echo ""

# ---------------------------------------------------------------------------
# Seeders (only runs when RUN_SEEDERS=true is set in Render env vars)
# ---------------------------------------------------------------------------
if [ "$RUN_SEEDERS" = "true" ]; then
    echo ""
    echo "========================================"
    echo "  DATABASE SEEDERS"
    echo "========================================"
    php artisan db:seed --force --verbose 2>&1
    SEED_EXIT=$?
    if [ $SEED_EXIT -eq 0 ]; then
        echo "==> [OK] Seeders completed successfully"
    else
        echo "==> [FAIL] Seeders failed (exit $SEED_EXIT)"
    fi
    echo "========================================"
    echo ""
else
    echo "==> Seeders skipped (set RUN_SEEDERS=true in Render env to run them)"
fi

echo "==> Starting Apache"
exec /usr/local/bin/apache2-foreground
