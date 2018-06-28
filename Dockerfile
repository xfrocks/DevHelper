FROM xfrocks/xenforo:php-apache-7.2.7

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV DEVHELPER_PHP_APACHE_VERSION_ID 2018062801

COPY docker/* /usr/local/bin/
RUN /usr/local/bin/build.sh

WORKDIR "/var/www/html/src/addons"
