#!/bin/bash

for _verb in build-release \
  bump-version \
  disable \
  enable \
  export \
  install \
  install-step \
  rebuild \
  sync-json \
  uninstall \
  uninstall-step \
  upgrade \
  upgrade-step \
  validate-json \
  ; do
  _verbPath="/usr/local/bin/xf-addon--${_verb}"
  {
    echo '#!/bin/bash'; \
    echo; \
    echo 'set -e'; \
    echo; \
    echo "exec cmd-php--xf-addon.sh ${_verb} \"\$@\"";
  } >"${_verbPath}"
  chmod +x "${_verbPath}"
done
