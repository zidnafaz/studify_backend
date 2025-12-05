#!/bin/sh

# Clear caches to ensure fresh config
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Generate JWT secret
echo "Generating JWT secret..."
php artisan jwt:secret --force || true

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisord.conf
