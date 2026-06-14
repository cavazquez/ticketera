FROM php:8.3-fpm

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update && apt-get install -y \
    git unzip \
    && install-php-extensions pdo_mysql mbstring xml ldap opcache imap redis \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/custom.ini /usr/local/etc/php/conf.d/99-ticketera.ini
COPY docker/php/fpm-pool.conf /usr/local/etc/php-fpm.d/zz-ticketera.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
