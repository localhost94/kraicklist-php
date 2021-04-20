FROM php:8-fpm

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        libssl-dev \
        zip \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install exif zip

RUN pecl install mongodb

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

EXPOSE 9000

WORKDIR /app
