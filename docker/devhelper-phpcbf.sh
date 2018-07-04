#!/bin/bash

set -e

export PHPCBF=1

exec devhelper-phpcs.sh "$@"
