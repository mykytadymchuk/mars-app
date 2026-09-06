FROM php:8.3-fpm

RUN apt-get update \
    && apt-get install -y \
        libssl-dev \
        pkg-config \
        libcurl4-openssl-dev \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
