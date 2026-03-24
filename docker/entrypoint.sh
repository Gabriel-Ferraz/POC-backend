#!/bin/sh
set -e

echo "Setting up storage permissions..."
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

echo "Configuring directory permissions..."
find /app/storage /app/bootstrap/cache -type d -exec chmod g+s {} \;

echo "Creating storage symbolic link..."
cd /app && php artisan storage:link

echo "Starting supervisor services..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n
