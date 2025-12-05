#!/bin/sh

# Ensure storage directory exists
mkdir -p /var/www/html/storage/app

# Write Firebase credentials to file if env var exists
if [ ! -z "$FIREBASE_CREDENTIALS_JSON" ]; then
    echo "Writing Firebase credentials to file..."
    echo "$FIREBASE_CREDENTIALS_JSON" > /var/www/html/storage/app/studify-70054-firebase-adminsdk-fbsvc-a95575e57b.json
fi

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
