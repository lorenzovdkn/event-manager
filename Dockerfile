FROM composer:latest AS composer
FROM php:latest

RUN apt-get update && apt-get install -y unzip git
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html
