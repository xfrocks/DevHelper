FROM xfrocks/xenforo:php-apache-7.2.8c

RUN composer global require phpstan/phpstan:0.10.3 && mv /tmp/vendor /etc/devhelper-composer-vendor

ENV DEVHELPER_PHP_APACHE_VERSION_ID 2018082301

COPY docker/build.sh /tmp/build.sh
RUN chmod +x /tmp/build.sh && /tmp/build.sh

WORKDIR "/var/www/html/src/addons"
