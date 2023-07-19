#DEV ONLY!
FROM php:7.4

RUN apt-get update && apt-get install zip git -y

RUN pecl install pecl install xdebug-3.1.6 \
  && docker-php-ext-enable xdebug

RUN pecl install mongodb \
  && docker-php-ext-enable mongodb

COPY docker/php.ini /usr/local/etc/php/

RUN touch /var/log/php-errors.log

COPY --from=composer/composer:2.5 /usr/bin/composer /usr/bin/composer
ENTRYPOINT composer update --prefer-stable --prefer-dist --no-plugins --no-scripts \
    && tail -f /var/log/php-errors.log
