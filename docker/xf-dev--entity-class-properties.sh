#!/bin/bash

set -e

_addOnOrEntity="${1%/}"
if [ -z "${_addOnOrEntity}" ]; then
  echo 'Add-on or entity is missing' >&2
  exit 1
fi

exec cmd-php.sh xf-dev:entity-class-properties "${_addOnOrEntity}"
