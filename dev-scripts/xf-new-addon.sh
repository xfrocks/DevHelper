#!/bin/bash

if [ $# -ge 2 ]; then

    ADDON_ID="$1"
    PATH_TO_XENFORO="$2"
    XENFORO_VERSION=1
    RELATIVE_PATH_TO_ADDON_SRC=""

    # get the addon directory by replace all "_" with "/"
    # we will get a sub directory structure with this kind of subsitution
    # the DevHelper add-on uses a similar structure...
    ADDON_DIR=${ADDON_ID//_/\/}

    if [ -d "${PATH_TO_XENFORO}" ]; then
        if [ -d "${PATH_TO_XENFORO}/src/addons/" ]; then
            XENFORO_VERSION=2
            echo "XenForo 2 detected"
            RELATIVE_PATH_TO_ADDON_SRC="src/addons/${ADDON_ID}"
        else
            if [ -d "${PATH_TO_XENFORO}/library/" ]; then
                echo "XenForo 1 detected"
                RELATIVE_PATH_TO_ADDON_SRC="library/${ADDON_ID}"
            else
                echo "./library or ./src/addons" does not exists! Quit now...
                exit 1
            fi
        fi
    else
        echo "${PATH_TO_XENFORO}" does not exists! Quit now...
        exit 1
    fi

    if [ ! -d "${ADDON_DIR}" ]; then
        echo Creating add-on directory, enter UNIX user password if asked...
        sudo mkdir -p -m 0777 "${ADDON_DIR}"
        sudo mkdir -p -m 0777 "${ADDON_DIR}/repo/${RELATIVE_PATH_TO_ADDON_SRC}"
        sudo mkdir -p -m 0777 "${ADDON_DIR}/repo/js/${ADDON_DIR}"
        sudo mkdir -p -m 0777 "${ADDON_DIR}/repo/styles/default/${ADDON_DIR}"
        sudo chown -R $USER "${ADDON_DIR}"
    fi
    
    if [ ! -d "${PATH_TO_XENFORO}/${RELATIVE_PATH_TO_ADDON_SRC}" ]; then
        echo Creating add-on symbolic links in XenForo directories, enter UNIX user password if asked...
        sudo ln -s "${PWD}/${ADDON_DIR}/repo/${RELATIVE_PATH_TO_ADDON_SRC}" "${PATH_TO_XENFORO}/${RELATIVE_PATH_TO_ADDON_SRC}"
        sudo chown -h $USER "${PATH_TO_XENFORO}/${RELATIVE_PATH_TO_ADDON_SRC}"
        if [ -f "${PATH_TO_XENFORO}/library/.gitignore" ]; then
            sudo echo "${ADDON_DIR}" >> "${PATH_TO_XENFORO}/library/.gitignore"
        fi
    fi

    if [ ! -d "${PATH_TO_XENFORO}/js/${ADDON_DIR}" ]; then
        sudo ln -s "${PWD}/${ADDON_DIR}/repo/js/${ADDON_DIR}" "${PATH_TO_XENFORO}/js/${ADDON_DIR}"
        sudo chown -h $USER "${PATH_TO_XENFORO}/js/${ADDON_DIR}"
        if [ -f "${PATH_TO_XENFORO}/js/.gitignore" ]; then
            sudo echo "${ADDON_DIR}" >> "${PATH_TO_XENFORO}/js/.gitignore"
        fi
    fi

    if [ ! -d "${PATH_TO_XENFORO}/styles/default/${ADDON_DIR}" ]; then
        sudo ln -s "${PWD}/${ADDON_DIR}/repo/styles/default/${ADDON_DIR}" "${PATH_TO_XENFORO}/styles/default/${ADDON_DIR}"
        sudo chown -h $USER "${PATH_TO_XENFORO}/styles/default/${ADDON_DIR}"
        if [ -f "${PATH_TO_XENFORO}/styles/default/.gitignore" ]; then
            sudo echo "${ADDON_DIR}" >> "${PATH_TO_XENFORO}/styles/default/.gitignore"
        fi
    fi

    if [ ! -e "${ADDON_DIR}/repo/.gitignore" ]; then
        echo Initializing git repo...
        cd "${ADDON_DIR}/repo/"
        git init
        echo '.DS_Store' >> .gitignore
        echo '*.devhelper' >> .gitignore
        echo "FileSums.php" >> .gitignore
        echo "library/${ADDON_DIR}/DevHelper/Generated" >> .gitignore
        echo "library/${ADDON_DIR}/DevHelper/XFCP" >> .gitignore
        git add .
        git commit -m 'Initialized by xf-new-addon script'
    fi
else
    echo USAGE: $0 [addOnId] [path/to/xenforo/root]
fi
