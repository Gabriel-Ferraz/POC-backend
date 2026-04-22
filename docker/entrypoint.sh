#!/bin/sh
set -e

echo "Setting up storage permissions..."
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

echo "Configuring directory permissions..."
find /app/storage /app/bootstrap/cache -type d -exec chmod g+s {} \;

echo "Setting up Octane state directory..."
mkdir -p /app/storage/octane
chown -R www-data:www-data /app/storage/octane
chmod -R 775 /app/storage/octane

echo "Creating storage symbolic link..."
su -s /bin/sh www-data -c "php /app/artisan storage:link --force" 2>/dev/null || true

echo "Optimize cache config..."
su -s /bin/sh www-data -c "php /app/artisan event:cache"

echo "Starting supervisor services..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n
