#!/bin/bash

set -e

_addOnId="${1%/}"
if [ -z "${_addOnId}" ]; then
  echo 'Add-on ID is missing' >&2
  exit 1
fi

devhelper-autogen.sh "${_addOnId}"

_phpcs=$( phpcs--addon.sh "${_addOnId}" 2>&1 || true )
if [ ! -z "$_phpcs" ]; then
  echo "$_phpcs"

  _phpcbfSuggestion=$( echo "$_phpcs" | grep 'PHPCBF CAN FIX' )
  if [ ! -z "$_phpcbfSuggestion" ]; then
    echo "phpcs failed, execute \`phpcbf--addon.sh ${_addOnId}\` to attempt fixing automatically" >&2
    exit 2
  fi

  echo 'phpcs failed' >&2
  exit 1
fi
echo 'phpcs OK'

exec cmd-php.sh xf-addon:export "${_addOnId}"
