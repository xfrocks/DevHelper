#!/bin/sh

set -e

_srcPath="${1%/}"
if [ -z "${_srcPath}" ]; then
  echo 'Source path is missing' >&2
  exit 1
fi

exec /etc/devhelper-composer-vendor/bin/phpstan analyse \
  --autoload-file=/var/www/html/src/addons/DevHelper/PHPStan/autoload.php \
  --level max \
  -c /var/www/html/src/addons/DevHelper/PHPStan/phpstan.neon \
  "${_srcPath}"
