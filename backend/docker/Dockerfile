FROM php:8.4-fpm AS production

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql intl zip opcache gd \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock* ./
RUN if [ -f composer.lock ]; then composer install --no-dev --optimize-autoloader --no-scripts --no-interaction --ignore-platform-reqs; else composer update --no-dev --optimize-autoloader --no-scripts --no-interaction --ignore-platform-reqs; fi

COPY . /var/www
RUN chown -R www-data:www-data /var/www

COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

EXPOSE 9000
CMD ["php-fpm"]
