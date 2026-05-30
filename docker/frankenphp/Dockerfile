# syntax=docker/dockerfile:1

# ---- Stage 1: build front-end assets ----------------------------------------
FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
RUN npm ci
COPY resources ./resources
RUN npm run build

# ---- Stage 2: PHP runtime (FrankenPHP + Octane) -----------------------------
FROM dunglas/frankenphp:1-php8.3 AS app

# Native extensions BitePlate needs: Postgres, LDAP, Redis, Kafka, plus the usual.
RUN install-php-extensions \
        pdo_pgsql \
        pgsql \
        ldap \
        redis \
        rdkafka \
        intl \
        zip \
        bcmath \
        pcntl \
        opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP dependencies first for better layer caching.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

# Application source + built assets.
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/frankenphp/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 8000
ENTRYPOINT ["entrypoint"]
CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000"]
