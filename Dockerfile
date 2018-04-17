FROM xfrocks/xenforo:php-apache-7.2.4

ENV DEVHELPER_PHP_APACHE_VERSION_ID 2018041701

COPY docker/* /usr/local/bin/
RUN /usr/local/bin/build.sh

WORKDIR "/var/www/html/src/addons"
