#!/bin/bash

set -e

_addOnId="${1%/}"
if [ -z "${_addOnId}" ]; then
  echo 'Add-on ID is missing' >&2
  exit 1
fi

_phpcs=$( xf-addon--phpcs.sh "${_addOnId}" || true )
if [ ! -z "$_phpcs" ]; then
  echo "$_phpcs"

  _phpcbfSuggestion=$( echo "$_phpcs" | grep 'PHPCBF CAN FIX' )
  if [ ! -z "$_phpcbfSuggestion" ]; then
    xf-addon--phpcbf.sh "${_addOnId}"
    echo 'phpcs failed, phpcbf OK'
    exit 2
  fi

  echo 'phpcs failed'
  exit 1
fi
echo 'phpcs OK'

exec cmd-php.sh xf-addon:export "${_addOnId}"
