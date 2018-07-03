#!/bin/sh

set -e

_addOnId="${1%/}"
if [ -z "${_addOnId}" ]; then
  echo 'Add-on ID is missing' >&2
  exit 1
fi

exec phpstan analyse \
  --autoload-file=/var/www/html/src/addons/DevHelper/docker/phpstan/autoload.php \
  --level max \
  "${_addOnId}"
