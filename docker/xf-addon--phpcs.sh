#!/bin/bash

set -e

_phpcsXmlPath='/usr/local/bin/phpcs.xml'
set -- "--standard=${_phpcsXmlPath}" "$@"

if [ -z "$PHPCBF" ]; then
  set -- phpcs -s "$@"
else
  set -- phpcbf "$@"
fi

exec "$@"
