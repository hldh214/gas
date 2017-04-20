FROM alpine:3.5

MAINTAINER Jim "https://github.com/hldh214"

RUN apk add --update bash nginx php7-fpm php7-xml php7-openssl supervisor \
    && rm -rf /var/cache/apk/* \
    && mkdir -p /run/nginx /var/www/html/tmp /var/www/html/preview \
    && chown nobody:nobody /var/www/html/tmp \
    && chown nobody:nobody /var/www/html/preview

COPY supervisord.conf /etc/supervisord.conf
COPY default /etc/nginx/conf.d/default.conf
COPY index.html /var/www/html/index.html
COPY intro.html /var/www/html/intro.html
COPY ajax.php /var/www/html/ajax.php
COPY func.php /var/www/html/func.php
COPY JLib.php /var/www/html/JLib.php
COPY Wechat.php /var/www/html/Wechat.php
COPY site.min.css /var/www/html/site.min.css

EXPOSE 80

CMD ["/usr/bin/supervisord"]