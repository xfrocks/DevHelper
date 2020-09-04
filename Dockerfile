# https://hub.docker.com/r/xfrocks/xenforo/tags/
FROM xfrocks/xenforo:php-apache-7.3.12c

# https://packagist.org/packages/phpstan/phpstan
# https://packagist.org/packages/phpstan/phpstan-strict-rules
RUN composer global require \
        phpstan/phpstan:0.12.38 \
        phpstan/phpstan-strict-rules:0.12.4 \
    && mv /tmp/vendor /etc/devhelper-composer-vendor

ENV DEVHELPER_PHP_APACHE_VERSION_ID 2020082201

COPY docker/build.sh /tmp/build.sh
RUN chmod +x /tmp/build.sh && /tmp/build.sh

WORKDIR "/var/www/html/src/addons"
