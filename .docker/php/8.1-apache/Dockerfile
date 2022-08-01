FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
        libzip-dev

RUN apt-get install -y default-mysql-client

RUN pecl install zip \
    && docker-php-ext-enable zip \
    && docker-php-ext-install pdo_mysql


RUN a2enmod rewrite

COPY etc/sites-available/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

