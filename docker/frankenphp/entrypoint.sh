#!/bin/sh
set -e

# Wait for Postgres to accept connections.
echo "Waiting for the database…"
until php -r "exit(@fsockopen(getenv('DB_HOST') ?: 'db', (int)(getenv('DB_PORT') ?: 5432)) ? 0 : 1);" 2>/dev/null; do
    sleep 1
done

php artisan config:clear

# Only the web app should run migrations/seeders; workers skip this.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
    php artisan db:seed --force || true
fi

exec "$@"
