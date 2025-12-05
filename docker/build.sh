# Build command for Render
# This file is used by Render.com to build the Docker image

# Install dependencies and build assets
composer install --no-dev --optimize-autoloader --no-interaction
npm install
npm run build

# Build assets
npm run build
