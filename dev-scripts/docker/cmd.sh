#!/bin/bash

set -e

find-addons.sh

exec apache2-foreground
