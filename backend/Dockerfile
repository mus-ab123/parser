FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    git \
    zip \
    unzip \
    icu-dev \
    libzip-dev \
    mysql-client \
    oniguruma-dev \
    nodejs \
    npm \
    chromium \
    nss \
    freetype \
    harfbuzz \
    ca-certificates \
    ttf-freefont \
    && docker-php-ext-install intl pdo pdo_mysql zip bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser

COPY . /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN cd scraper && npm install --production

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

CMD ["sh", "-c", "php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]

