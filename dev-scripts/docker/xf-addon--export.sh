#!/bin/bash

set -e

_addOnId="${1%/}"
if [ -z "${_addOnId}" ]; then
  echo 'Add-on ID is missing' >&2
  exit 1
fi

xf-addon--phpcs.sh "${_addOnId}"
echo 'phpcs OK'

exec cmd-php.sh xf-addon:export "${_addOnId}"
