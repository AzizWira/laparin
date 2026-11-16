# syntax=docker/dockerfile:1

FROM php:8.3-apache-bookworm AS php-base
WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl libicu-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libzip-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j2 gd intl pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

FROM php-base AS dependencies
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY composer.json composer.lock ./

FROM dependencies AS test
RUN composer install --no-interaction --prefer-dist --no-autoloader --no-scripts
COPY . .
RUN composer dump-autoload --optimize --no-interaction
ENV APP_ENV=testing
CMD ["sh", "-ec", "APP_KEY=$(php artisan key:generate --show --no-interaction); export APP_KEY; exec php vendor/bin/phpunit"]

FROM dependencies AS application
RUN composer install --no-dev --no-interaction --prefer-dist --no-autoloader --no-scripts
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction

FROM php-base AS runtime
RUN a2enmod rewrite && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY --from=application --chown=www-data:www-data /var/www/html/ /var/www/html/
COPY --chmod=0755 docker/start.sh /usr/local/bin/laparin-start

HEALTHCHECK --interval=8s --timeout=4s --start-period=30s --retries=6 \
    CMD curl --fail --silent --output /dev/null --max-time 3 http://127.0.0.1/health || exit 1

CMD ["laparin-start"]
