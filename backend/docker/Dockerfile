FROM php:8.4-cli AS build

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install intl zip gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock* ./
RUN if [ -f composer.lock ]; then composer install --no-dev --optimize-autoloader --no-scripts --no-interaction --ignore-platform-reqs; else composer update --no-dev --optimize-autoloader --no-scripts --no-interaction --ignore-platform-reqs; fi

# ─── Production ───────────────────────────────────────────────
FROM php:8.4-fpm AS production

RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    librabbitmq-dev \
    libsqlite3-dev \
    pkg-config \
    libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && SQLITE_LIBS="-lsqlite3" docker-php-ext-install pdo_mysql pdo_sqlite intl zip opcache gd \
    && pecl install amqp && docker-php-ext-enable amqp \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY --from=build /var/www/vendor vendor/
COPY . .
RUN chown -R www-data:www-data /var/www

COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

EXPOSE 9000
CMD ["php-fpm"]
