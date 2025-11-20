FROM composer:2 AS composer
FROM php:8.4-fpm

RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev libzip-dev unzip \
 && docker-php-ext-install pdo pdo_pgsql zip opcache \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000