#!/bin/sh

_addOnId="${1}"
_dir="${2}"

if [ -z "$_addOnId" -o -z "$_dir" ]; then
  echo Usage: devhelper--build-release-to-dir Vendor/AddOnId dir_name
  exit 1
fi

export DEVHELPER_ZIP_ARCHIVE_TO_DIR="${_dir}"

exec xf-addon--build-release "${_addOnId}"
