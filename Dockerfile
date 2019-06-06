FROM xfrocks/xenforo:php-apache-7.3.3b

RUN composer global require \
        phpstan/phpstan:0.11.3 \
        phpstan/phpstan-strict-rules:0.11 \
    && mv /tmp/vendor /etc/devhelper-composer-vendor

ENV DEVHELPER_PHP_APACHE_VERSION_ID 2019031301

COPY docker/build.sh /tmp/build.sh
RUN chmod +x /tmp/build.sh && /tmp/build.sh

WORKDIR "/var/www/html/src/addons"
