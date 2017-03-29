FROM ubuntu:16.04

MAINTAINER Jim "https://github.com/hldh214"

RUN apt-get update \
    && apt-get install -y nginx php7.0-fpm php7.0-mbstring php7.0-xml supervisor \
    && mkdir -p /var/log/supervisor /run/php /var/www/html/tmp /var/www/html/preview \
    && chown www-data:www-data /var/www/html/tmp \
    && chown www-data:www-data /var/www/html/preview

COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY default /etc/nginx/sites-available/default
COPY index.html /var/www/html/index.html
COPY intro.html /var/www/html/intro.html
COPY ajax.php /var/www/html/ajax.php
COPY func.php /var/www/html/func.php
COPY JLib.php /var/www/html/JLib.php
COPY Wechat.php /var/www/html/Wechat.php
COPY site.min.css /var/www/html/site.min.css

EXPOSE 80

CMD ["/usr/bin/supervisord"]