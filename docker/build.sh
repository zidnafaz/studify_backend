# Build command for Render
# This file is used by Render.com to build the Docker image

# Install dependencies and build assets
composer install --no-dev --optimize-autoloader --no-interaction
npm install
npm run build

# Generate optimized configs
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force
