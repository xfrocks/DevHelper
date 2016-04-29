#!/bin/bash

PATH_TO_XENFORO=`pwd`
PATH_TO_REPO=$(cd `dirname "${BASH_SOURCE[0]}"` && cd ../../.. && pwd)

# install DevHelper if haven't done that yet
cd $PATH_TO_REPO && $PATH_TO_REPO/DevHelper/repo/dev-scripts/xf-new-addon.sh DevHelper $PATH_TO_XENFORO

if [ ! -e "$PATH_TO_XENFORO/docker-compose.override.yml" ]; then
	ln -s $PATH_TO_REPO/DevHelper/repo/docker-compose.yml $PATH_TO_XENFORO/docker-compose.yml
	( \
    echo "version: '2'"; \
    echo ""; \
    echo "services:"; \
    echo "  php-apache:"; \
    echo "    ports:"; \
    echo "      - "$RANDOM:80""; \
    ) > $PATH_TO_XENFORO/docker-compose.override.yml
fi
