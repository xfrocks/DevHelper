#!/bin/bash

set -e

_addOnId="${1%/}"
if [ -z "${_addOnId}" ]; then
  echo 'Add-on ID is missing' >&2
  exit 1
fi

echo 'xf-addon--export.sh is no longer functional,'
echo "Use \`cmd-php--xf-addon.sh export ${_addOnId}\` if you have to..."
echo "For other usages, please use \`xf-dev--export--addon.sh ${_addOnId}\` instead."
exit 1
