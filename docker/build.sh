#!/bin/bash

set -e

echo 'PassEnv DEVHELPER_PHP_APACHE_VERSION_ID' >> /etc/apache2/mods-available/env.conf
a2enmod env rewrite

{ \
  echo '<VirtualHost *:80>'; \
  echo '  include /var/www/html/src/addons/DevHelper/docker/apache.conf'; \
  echo '</VirtualHost>'; \
} > /etc/apache2/sites-enabled/000-default.conf

{ \
  echo '<VirtualHost _default_:443>'; \
  echo '  include /var/www/html/src/addons/DevHelper/docker/apache.conf'; \
  echo '  SSLEngine on'; \
  echo '  SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem'; \
  echo '  SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key'; \
  echo '</VirtualHost>'; \
} > /etc/apache2/sites-enabled/default-ssl.conf

echo 'export "PATH=$PATH:/var/www/html/src/addons/DevHelper/docker"' >> /root/.bashrc

echo 'auto_prepend_file=/var/www/html/src/addons/DevHelper/prepend.php' > /usr/local/etc/php/conf.d/DevHelper.ini

for _verb in build-release \
  bump-version \
  disable \
  enable \
  install \
  install-step \
  rebuild \
  sync-json \
  uninstall \
  uninstall-step \
  upgrade \
  upgrade-step \
  validate-json \
  ; do
  _verbPath="/usr/local/bin/xf-addon--${_verb}"
  {
    echo '#!/bin/bash'; \
    echo; \
    echo 'set -e'; \
    echo; \
    echo "exec cmd-php--xf-addon.sh ${_verb} \"\$@\"";
  } >"${_verbPath}"
  chmod +x "${_verbPath}"
done

rm -rf /tmp/*
