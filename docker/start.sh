#!/bin/sh

# Ensure storage directory exists
mkdir -p /var/www/html/storage/app

# Handle Firebase Credentials
# If FIREBASE_CREDENTIALS starts with '{', it's JSON content, not a path.
# We need to write it to a file and update the env var to point to that file.
if echo "$FIREBASE_CREDENTIALS" | grep -q '^{'; then
    echo "Detected JSON content in FIREBASE_CREDENTIALS. Writing to file..."
    echo "$FIREBASE_CREDENTIALS" > /var/www/html/storage/app/firebase.json
    export FIREBASE_CREDENTIALS=/var/www/html/storage/app/firebase.json
elif [ ! -z "$FIREBASE_CREDENTIALS_JSON" ]; then
    # Fallback: If FIREBASE_CREDENTIALS_JSON is set, use that
    echo "Writing FIREBASE_CREDENTIALS_JSON to file..."
    echo "$FIREBASE_CREDENTIALS_JSON" > /var/www/html/storage/app/firebase.json
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
