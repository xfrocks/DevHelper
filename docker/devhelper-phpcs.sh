#!/bin/bash

set -e

_phpcsXmlPath='/var/www/html/src/addons/DevHelper/phpcs.xml'
set -- "--standard=${_phpcsXmlPath}" "$@"

if [ -z "$PHPCBF" ]; then
  set -- phpcs -s "$@"
else
  set -- phpcbf "$@"
fi

exec "$@"
