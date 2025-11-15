#!/bin/sh

# Wait for database to be ready
echo "Waiting for database connection..."
until php artisan migrate:status > /dev/null 2>&1
do
    echo "Database is unavailable - sleeping"
    sleep 2
done

echo "Database is up - executing migrations"
php artisan migrate --force

# Generate JWT secret if not exists
php artisan jwt:secret --force || true

# Cache configurations for better performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisord.conf
