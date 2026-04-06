#!/bin/sh
set -e

echo "Setting up storage permissions..."
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

echo "Configuring directory permissions..."
find /app/storage /app/bootstrap/cache -type d -exec chmod g+s {} \;

echo "Creating storage symbolic link..."
php /app/artisan storage:link --force 2>/dev/null || true

echo "Optimize cache config..."
php /app/artisan event:cache && php /app/artisan route:cache

echo "Starting supervisor services..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n
