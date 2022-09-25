FROM composer:latest AS composer
FROM php:8.0-cli-alpine
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache oniguruma-dev && \
    docker-php-ext-install sockets && \
    apk del oniguruma-dev

COPY . /app
WORKDIR /app

RUN composer install --no-dev && rm -rf composer.json composer.lock

CMD ["php", "./logger.php"]