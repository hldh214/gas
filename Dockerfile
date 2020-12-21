FROM php:8.0-cli

WORKDIR /var/www/html

COPY . /var/www/html/

RUN apt-get update \
    && apt-get install -y zip libzip-dev unzip git supervisor redis \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer \
    && docker-php-ext-install opcache zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && composer install --no-dev \
    && cp .env.example .env \
    && php artisan key:generate

EXPOSE 8964

CMD [ "/usr/bin/supervisord" ]
