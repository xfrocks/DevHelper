FROM xfrocks/xenforo:php-apache-7.2.3

COPY docker/* /usr/local/bin/
RUN chmod +x /usr/local/bin/*.sh

RUN /usr/local/bin/build.sh

WORKDIR "/var/www/html/src/addons"
