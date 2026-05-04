#!/bin/bash
set -e

sed -i 's/\r//' "$0"

echo "Installing PHP dependencies..."
if [ ! -d "vendor" ] || [ "composer.json" -nt "vendor/autoload.php" ] || [ ! -d "vendor/barryvdh" ]; then
    # Tenta install (mais rápido); se o lock estiver desatualizado cai no update
    composer install --no-interaction --prefer-dist --no-scripts 2>&1 \
        | tee /tmp/composer_out.txt \
        || true
    if grep -q "not present in the lock file\|lock file is not up to date" /tmp/composer_out.txt; then
        echo "Lock file desatualizado, rodando composer update..."
        composer update --no-interaction --prefer-dist --no-scripts
    fi
    composer dump-autoload --optimize
fi

echo "Running migrations..."
php artisan db:fresh-oracle

echo "Creating storage symlink..."
php artisan storage:link 2>/dev/null || true

if [ "${SEED_DB:-false}" = "true" ]; then
    echo "Seeding database..."
    php artisan db:seed --force --no-interaction
fi

echo "Starting Laravel development server..."
exec php artisan serve --host=0.0.0.0 --port=3333

# echo "Starting Laravel Octane..."
# exec php artisan octane:start --server=swoole --host=0.0.0.0 --port=3333
