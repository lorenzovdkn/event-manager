FROM composer:2 AS composer
FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
 && docker-php-ext-install pdo pdo_pgsql zip opcache \
 && rm -rf /var/lib/apt/lists/*

# Configure OPcache for production performance
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.save_comments=1'; \
    echo 'opcache.fast_shutdown=1'; \
} > /usr/local/etc/php/conf.d/opcache-prod.ini

# Configure PHP for production
RUN { \
    echo 'display_errors=Off'; \
    echo 'error_reporting=E_ALL'; \
    echo 'log_errors=On'; \
    echo 'memory_limit=256M'; \
    echo 'post_max_size=20M'; \
    echo 'upload_max_filesize=10M'; \
} > /usr/local/etc/php/conf.d/php-prod.ini

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000
