#!/bin/sh

set -e

_srcPath="${1%/}"
if [ -z "${_srcPath}" ]; then
  echo 'Source path is missing' >&2
  exit 1
fi

_checkPath=${2:-${_srcPath}}

_neonPath='/var/www/html/src/addons/DevHelper/PHPStan/phpstan.neon'
_srcNeonPath="${_srcPath}/_files/dev/phpstan.neon"
if [ -f ${_srcNeonPath} ]; then
  _neonPath=${_srcNeonPath}
  echo "Using ${_neonPath}" >&2
fi

export "DEVHELPER_PHPSTAN_SRC_PATH=${_srcPath}"

echo "Running PHPStan against ${_checkPath}..."
exec /etc/devhelper-composer-vendor/bin/phpstan analyse \
  --autoload-file=/var/www/html/src/addons/DevHelper/PHPStan/autoload.php \
  --level max \
  --memory-limit=-1 \
  -c ${_neonPath} \
  ${_checkPath}
