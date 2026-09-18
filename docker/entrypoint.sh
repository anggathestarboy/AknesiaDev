#!/bin/sh
set -e

cd /app

echo "Waiting for the database..."
until php artisan migrate:status > /dev/null 2>&1; do
    sleep 2
done

php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
