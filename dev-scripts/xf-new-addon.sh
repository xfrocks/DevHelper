#!/bin/bash

set -e

if [ $# -ge 2 ]; then
    _addOnDirRelativePath="$1"
    _xenforoRootDirPath="$2"

    _addOnDirRelativePath=$( echo $_addOnDirRelativePath | sed 's#_#/#' | sed 's#/$##' )
    if [ -d "${_addOnDirRelativePath}/repo" ]; then
        # legacy support
        _repoDirRelativePath="${_addOnDirRelativePath}/repo"
    else
        _repoDirRelativePath="${_addOnDirRelativePath}"
    fi

    _xenforoMajorVersion=1
    _phpRelativePath=""
    _xenforoRootDirPath=$( echo $_xenforoRootDirPath | sed 's#/$##' )
    if [ -d "${_xenforoRootDirPath}" ]; then
        if [ -d "${_xenforoRootDirPath}/src/addons/" ]; then
            _xenforoMajorVersion=2
            echo "XenForo 2 detected"
            _phpRelativePath="src/addons/${_addOnDirRelativePath}"
        else
            if [ -d "${_xenforoRootDirPath}/library/" ]; then
                echo "XenForo 1 detected"
                _phpRelativePath="library/${_addOnDirRelativePath}"
            else
                echo './library or ./src/addons does not exists!' >&2
                exit 1
            fi
        fi
    else
        echo "${_xenforoRootDirPath} does not exists!" >&2
        exit 1
    fi

    _doSubPath() {
        _subPath=$1
        _path="${PWD}/${_repoDirRelativePath}/${_subPath}"
        _gitignorePath="${_xenforoRootDirPath}/.gitignore"
        _xenforoPath="${_xenforoRootDirPath}/${_subPath}"

        if [ ! -d "${_path}" ]; then
            mkdir -p "${_path}"
        fi

        if [ ! -e "${_xenforoPath}" ]; then
            _xenforoPathDirPath="$( dirname "${_xenforoPath}" )"
            if [ ! -d "${_xenforoPathDirPath}" ]; then
                echo "mkdir ${_xenforoPathDirPath}..."
                sudo mkdir -p "${_xenforoPathDirPath}"
            fi

            echo "ln ${_path} ${_xenforoPath}..."
            sudo ln -s "${_path}" "${_xenforoPath}"
            sudo chown -h $USER "${_xenforoPath}"
            if [ -f "${_gitignorePath}" ]; then
                echo "/${_subPath}" >> "${_gitignorePath}"
            fi
        fi

        echo "Sub path ${_subPath} ok"
    }
    _doSubPath "${_phpRelativePath}"
    _doSubPath "js/${_addOnDirRelativePath}"
    _doSubPath "styles/default/${_addOnDirRelativePath}"

    if [ ! -e "${_repoDirRelativePath}/.git" ]; then
        git init -- "${_repoDirRelativePath}"
        echo "git init ${_repoDirRelativePath} ok"
    fi
else
    echo USAGE: $0 [addOnId] [path/to/xenforo/root]
fi
