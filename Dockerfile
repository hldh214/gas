FROM ubuntu:16.04

MAINTAINER Jim "https://github.com/hldh214"

RUN apt-get update
RUN apt-get install -y nginx php supervisor

RUN mkdir -p /var/log/supervisor
RUN mkdir -p /run/php
RUN mkdir -p /var/www/html/tmp
RUN mkdir -p /var/www/html/preview

RUN chown www-data:www-data /var/www/html/tmp
RUN chown www-data:www-data /var/www/html/preview

COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY default /etc/nginx/sites-available/default
COPY index.html /var/www/html/index.html
COPY ajax.php /var/www/html/ajax.php
COPY func.php /var/www/html/func.php
COPY site.min.css /var/www/html/site.min.css

EXPOSE 80

CMD ["/usr/bin/supervisord"]