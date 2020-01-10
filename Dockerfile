# https://hub.docker.com/r/xfrocks/xenforo/tags/
FROM xfrocks/xenforo:php-apache-7.3.7

# https://packagist.org/packages/phpstan/phpstan
# https://packagist.org/packages/phpstan/phpstan-strict-rules
RUN composer global require \
        phpstan/phpstan:0.11.16 \
        phpstan/phpstan-strict-rules:0.11.1 \
    && mv /tmp/vendor /etc/devhelper-composer-vendor

ENV DEVHELPER_PHP_APACHE_VERSION_ID 2019091801

COPY docker/build.sh /tmp/build.sh
RUN chmod +x /tmp/build.sh && /tmp/build.sh

WORKDIR "/var/www/html/src/addons"
