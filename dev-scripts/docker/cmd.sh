#!/bin/bash

set -e

find-addons2.sh

exec apache2-foreground
