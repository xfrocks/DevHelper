FROM xfrocks/xenforo:php-apache-7.2.7

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV DEVHELPER_PHP_APACHE_VERSION_ID 2018070302

COPY docker/build.sh /tmp/build.sh
RUN chmod +x /tmp/build.sh && /tmp/build.sh

WORKDIR "/var/www/html/src/addons"
