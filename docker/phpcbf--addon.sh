#!/bin/bash

set -e

export PHPCBF=1

exec phpcs--addon.sh "$@"
