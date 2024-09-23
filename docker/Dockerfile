# ビルド時にPHPバージョンを指定できるように引数を定義
ARG PHP_VERSION=8.2

FROM php:${PHP_VERSION}-fpm

# install php-ext
RUN apt-get update && apt-get install -y git wget gnupg vim unzip libxml2-dev libpng-dev libzip-dev libonig-dev default-mysql-client libldap2-dev\
  && docker-php-ext-install mbstring dom gd zip pdo_mysql ftp ldap\
  && apt-get clean

# install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html
