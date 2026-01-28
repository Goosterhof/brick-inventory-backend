# syntax=docker/dockerfile:1

# Build stage for PHP dependencies
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --optimize --no-dev

# Production image
FROM php:8.4-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    curl \
    libcurl \
    oniguruma-dev \
    libxml2-dev \
    sqlite-dev \
    && docker-php-ext-install \
    bcmath \
    curl \
    mbstring \
    pdo_sqlite \
    opcache

# Install turso-libsql extension for Turso database
ADD --chmod=0755 https://github.com/nickmalleson/turso-client-php/releases/latest/download/liblibsql_php.so /usr/local/lib/php/extensions/no-debug-non-zts-20240924/liblibsql_php.so
RUN echo "extension=liblibsql_php.so" > /usr/local/etc/php/conf.d/libsql.ini

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Configure OPcache for production
RUN echo 'opcache.memory_consumption=128' >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo 'opcache.interned_strings_buffer=8' >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo 'opcache.max_accelerated_files=10000' >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo 'opcache.revalidate_freq=0' >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo 'opcache.validate_timestamps=0' >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo 'opcache.enable_cli=1' >> /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# Copy application files
COPY --from=composer /app/vendor ./vendor
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create storage directories
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && chown -R www-data:www-data storage

# Cache configuration for production
RUN php artisan config:cache || true \
    && php artisan route:cache || true \
    && php artisan view:cache || true

EXPOSE 8080

USER www-data

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
