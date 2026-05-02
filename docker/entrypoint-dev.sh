#!/bin/bash
set -e

# Fix Windows CRLF (runtime safety net, also stripped at build time)
sed -i 's/\r//' "$0"

# Install PHP dependencies if needed
if [ ! -d "vendor" ] || [ "composer.json" -nt "vendor/autoload.php" ]; then
    echo "Installing PHP dependencies..."
    composer install --no-interaction --prefer-dist --no-scripts
    composer dump-autoload --optimize
fi

echo "Running migrations..."
php artisan migrate:fresh --force --no-interaction

echo "Creating storage symlink..."
php artisan storage:link 2>/dev/null || true

if [ "${SEED_DB:-false}" = "true" ]; then
    echo "Seeding database..."
    php artisan db:seed --force --no-interaction
fi

echo "Starting Laravel development server..."
exec php artisan serve --host=0.0.0.0 --port=3333
