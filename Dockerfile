# Use official PHP 8.2 FPM image (Debian based)
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    mariadb-client \
    nginx \
    supervisor \
    nodejs \
    npm \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions \
    pdo \
    pdo_mysql \
    mbstring \
    xml \
    bcmath \
    gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Copy nginx configuration
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/conf.d/default.conf

# Copy supervisor configuration
COPY docker/supervisord.conf /etc/supervisord.conf

# Copy startup script
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Create .env file
RUN cp .env.example .env && \
    echo "FIREBASE_PROJECT_ID=build_dummy" >> .env

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install Node dependencies and build assets
RUN npm install && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create log directories
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /var/log/nginx \
    && mkdir -p /var/log/php-fpm

# Expose port
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:8080/api/health || exit 1

# Start application
CMD ["/usr/local/bin/start.sh"]
