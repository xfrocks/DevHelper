#!/bin/bash

set -e

export PHPCBF=1

exec xf-addon--phpcs.sh "$@"
