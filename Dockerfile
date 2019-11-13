FROM composer:latest as composer
#WORKDIR /var/www
#COPY --chown=www-data:www-data . /var/www
#RUN composer install

FROM php:7.2-fpm
RUN apt-get update && apt-get install -y nginx libcurl4-openssl-dev pkg-config pkg-config libssl-dev libmcrypt-dev zip unzip\
    libmagickwand-dev --no-install-recommends \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && docker-php-ext-install pdo_mysql \
    && pecl install mcrypt-1.0.2 \
    && docker-php-ext-enable mcrypt \
    && pecl install mongodb \
    && echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongo.ini
RUN pecl config-set php_ini /etc/php.ini

COPY --chown=www-data:www-data . /var/www
RUN cp /var/www/.env.example /var/www/.env
#COPY --chown=www-data:www-data  /var/www/.env.example /var/www/.env

WORKDIR /var/www

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN composer install

#COPY --from=composer --chown=www-data:www-data  /var/www /var/www
#ADD vhost.conf /etc/nginx/conf.d/default.conf
#COPY --chown=www-data:www-data . /var/www
COPY vhost.conf /etc/nginx/sites-enabled/default
COPY entrypoint.sh /etc/entrypoint.sh

#RUN chown -R www-data:www-data /var/www

EXPOSE 80 443

ENTRYPOINT ["sh", "/etc/entrypoint.sh"]
