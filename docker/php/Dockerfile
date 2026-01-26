FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash git unzip zip icu-dev oniguruma-dev libzip-dev \
    mysql-client sqlite sqlite-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite intl mbstring zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY php.ini /usr/local/etc/php/conf.d/99-custom.ini

RUN echo "pm.max_children = 20" >> /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html
