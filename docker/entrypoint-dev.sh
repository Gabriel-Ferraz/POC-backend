#!/bin/bash
# Fix Windows CRLF line endings that would break bash execution
sed -i 's/\r//' "$0"

set -e

# Named volumes (app_vendor, app_node_modules) start empty on first run because
# the bind mount (.:/app) is applied before Docker can seed them from the image.
# Install dependencies here when the volumes are not yet populated.
if [ ! -f /app/vendor/autoload.php ]; then
    echo "Installing PHP dependencies..."
    composer install --no-interaction --prefer-dist --no-scripts
    composer dump-autoload --optimize
fi

echo "Running migrations..."
php artisan migrate

echo "Creating storage symlink..."
php artisan storage:link 2>/dev/null || true

if [ "${SEED_DB:-false}" = "true" ]; then
    echo "Seeding database..."
    php artisan db:seed
fi

echo "Starting development services..."
exec composer run dev:async
