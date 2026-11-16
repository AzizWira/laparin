# Stage 1: Build frontend assets
FROM node:22-alpine AS node-build

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build


# Stage 2: Install production dependencies
FROM php:8.3-cli AS composer-build

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libonig-dev \
    && docker-php-ext-install mbstring pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes
COPY artisan ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader


# Stage 3: Test application
FROM php:8.3-cli AS test

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libonig-dev \
    && docker-php-ext-install mbstring pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

COPY . .

RUN composer install --no-interaction --prefer-dist

RUN cp .env.example .env \
    && php artisan key:generate --force

RUN php artisan package:discover

COPY --from=node-build /app/public/build ./public/build

CMD ["php", "artisan", "test"]


# Stage 4: Production application
FROM php:8.3-cli AS production

RUN apt-get update && apt-get install -y \
    libzip-dev libonig-dev \
    && docker-php-ext-install mbstring pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=composer-build /var/www/html/vendor ./vendor
COPY . .
COPY --from=node-build /app/public/build ./public/build

RUN php artisan package:discover \
    && php artisan config:clear \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
