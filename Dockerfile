FROM xfrocks/xenforo:php-apache-7.2.7b

RUN composer global require phpstan/phpstan:0.10.3 && mv /tmp/vendor /etc/devhelper-composer-vendor

ENV DEVHELPER_PHP_APACHE_VERSION_ID 2018081701

COPY docker/build.sh /tmp/build.sh
RUN chmod +x /tmp/build.sh && /tmp/build.sh

WORKDIR "/var/www/html/src/addons"
