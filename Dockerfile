# ---- Frontend assets (Vite) ----
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

# ---- PHP dependencies ----
FROM php:8.2-cli-alpine AS vendor

RUN apk add --no-cache \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    && docker-php-ext-install zip mbstring pdo_pgsql bcmath pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --prefer-dist \
    --no-interaction

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

# ---- Production image ----
FROM php:8.2-fpm-alpine AS production

RUN apk add --no-cache \
    nginx \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    && docker-php-ext-install zip mbstring pdo_pgsql bcmath pcntl opcache \
    && rm -rf /var/cache/apk/*

WORKDIR /var/www/html

COPY docker/php.ini /usr/local/etc/php/conf.d/99-economyx.ini
COPY docker/nginx.conf /etc/nginx/http.d/default.conf.template
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

RUN mkdir -p storage/framework/{sessions,views,cache/data} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
