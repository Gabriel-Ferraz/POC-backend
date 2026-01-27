#!/bin/sh

# Enables write and read permissions on the file
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

# Enables setgid on directories
find /app/storage bootstrap/cache -type d -exec chmod g+s {} \;

# Create storage link for public file access
cd /app && php artisan storage:link

# Runs the supervisor
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n
