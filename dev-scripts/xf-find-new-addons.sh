#!/bin/sh

_xfPath=$1
_repoPath=$2

if [ ! -d "$_xfPath" ]; then
    echo "XenForo Path $_xfPath not found" >&2
    exit 1
fi

if [ ! -d "$_repoPath" ]; then
    echo "Repo Path $_repoPath not found" >&2
    exit 1
fi

_extractVersionId() {
    _filePath=$1
    _line=$(head -n 2 "$_filePath" | grep -o 'version_id="\d*"' )
    echo "$_line"
    exit 1
}

find "$_xfPath" -type f -name 'addon-*.xml' | while read -r _xfFilePath ; do
    _fileName=$( basename "$_xfFilePath" )
    _addOnId=$( echo "$_fileName" | sed 's/^addon-//' | sed 's/\.xml$//' )
    _xfVersionId=$( _extractVersionId "$_xfFilePath" )

    find "$_repoPath" -type f -name "$_fileName" | while read -r _repoFilePath ; do
        _repoVersionId=$( _extractVersionId "$_repoFilePath" )
        if [ "$_xfVersionId" != "$_repoVersionId" ]; then
            echo "$_addOnId:\n\tXenForo $_xfVersionId\n\tRepo    $_repoVersionId"
        fi
    done
done
