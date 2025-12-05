#!/bin/sh

# Ensure storage directory exists
mkdir -p /var/www/html/storage/app

# Handle Firebase Credentials
# Robust method: Decode Base64 env var to file
if [ ! -z "$FIREBASE_CREDENTIALS_BASE64" ]; then
    echo "Decoding FIREBASE_CREDENTIALS_BASE64 to file..."
    echo "$FIREBASE_CREDENTIALS_BASE64" | base64 -d > /var/www/html/storage/app/firebase.json
    export FIREBASE_CREDENTIALS=/var/www/html/storage/app/firebase.json
elif echo "$FIREBASE_CREDENTIALS" | grep -q '^{'; then
    # Fallback: JSON content
    echo "Detected JSON content in FIREBASE_CREDENTIALS. Writing to file..."
    echo "$FIREBASE_CREDENTIALS" > /var/www/html/storage/app/firebase.json
    export FIREBASE_CREDENTIALS=/var/www/html/storage/app/firebase.json
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
