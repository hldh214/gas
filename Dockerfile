FROM php:8.0-cli

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y zip libzip-dev unzip git \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer \
    && docker-php-ext-install opcache zip \
    && composer create-project hldh214/gas gas --repository="{\"url\": \"https://github.com/hldh214/gas.git\", \"type\": \"vcs\"}"

EXPOSE 8964

CMD [ "php", "gas/artisan", "serve", "--host=0.0.0.0", "--port=8964" ]
