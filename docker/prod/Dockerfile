# Production image for a single-container demo deploy (e.g. Render.com free tier).
# No nginx/php-fpm/redis/vite-dev-server here on purpose — the built-in PHP
# server plus SQLite keeps this to one process and one free-tier service.

FROM node:20-alpine AS frontend
WORKDIR /app
COPY backend/package.json backend/package-lock.json ./
# `npm ci` installs only the platform variants recorded in the lockfile, and
# rollup ships its binary as a per-platform optional dependency — so a lockfile
# written on one architecture breaks the build on another.
RUN npm install --no-audit --no-fund
COPY backend/ ./
RUN npm run build

FROM php:8.3-cli-alpine
RUN apk add --no-cache bash git unzip icu-dev oniguruma-dev libzip-dev sqlite sqlite-dev \
    && docker-php-ext-install pdo pdo_sqlite intl mbstring zip pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/prod/php.ini /usr/local/etc/php/conf.d/99-custom.ini

WORKDIR /var/www/html

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY backend/ ./
COPY --from=frontend /app/public/build ./public/build

RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache

COPY docker/prod/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV PHP_CLI_SERVER_WORKERS=4
EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
