FROM xfrocks/xenforo:php-apache-7.2.7b

RUN composer global require phpstan/phpstan && mv /tmp/vendor /etc/devhelper-composer-vendor

ENV DEVHELPER_PHP_APACHE_VERSION_ID 2018081601

COPY docker/build.sh /tmp/build.sh
RUN chmod +x /tmp/build.sh && /tmp/build.sh

WORKDIR "/var/www/html/src/addons"
