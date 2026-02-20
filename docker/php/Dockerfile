FROM php:8.1-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libxml2-dev \
        libzip-dev \
        libonig-dev \
        zlib1g-dev \
    && docker-php-ext-install mbstring zip dom simplexml xml xmlwriter \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
