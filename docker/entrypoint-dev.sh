#!/bin/bash
set -e

# Fix Windows CRLF (runtime safety net, also stripped at build time)
sed -i 's/\r//' "$0"

echo "Installing PHP dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "Generating application key..."
php artisan key:generate --force --no-interaction

echo "Running migrations..."
php artisan migrate --no-interaction

echo "Creating storage symlink..."
php artisan storage:link 2>/dev/null || true

if [ "${SEED_DB:-false}" = "true" ]; then
    echo "Seeding database..."
    php artisan db:seed --no-interaction
fi

echo "Starting development services..."
exec php artisan serve --host=0.0.0.0 --port=3333
