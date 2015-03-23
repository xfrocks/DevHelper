#!/bin/bash

PATH_TO_XENFORO=`pwd`
PATH_TO_REPO=$(cd `dirname "${BASH_SOURCE[0]}"` && cd ../../.. && pwd)

# install DevHelper if haven't done that yet
cd $PATH_TO_REPO
$PATH_TO_REPO/DevHelper/repo/dev-scripts/xf-new-addon.sh DevHelper $PATH_TO_XENFORO

cd $PATH_TO_XENFORO
php library/DevHelper/Installer.php

