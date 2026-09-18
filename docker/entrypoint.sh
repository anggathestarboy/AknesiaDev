#!/bin/sh
set -e

cd /app

# Only wait on the database and migrate when actually starting the server
# (the real "docker compose up" case) — a one-off command run via
# `docker compose run --rm --no-deps app php artisan ...` (e.g.
# key:generate --show, before .env even has DB credentials filled in)
# has no "db" container to wait for and nothing to migrate.
if [ "$1" = "supervisord" ]; then
    echo "Waiting for the database..."
    until php artisan migrate:status > /dev/null 2>&1; do
        sleep 2
    done

    php artisan migrate --force
    php artisan storage:link || true
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
