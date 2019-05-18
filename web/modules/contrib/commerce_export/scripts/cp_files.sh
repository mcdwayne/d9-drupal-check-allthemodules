#!/bin/bash
#
# Copy test files to migration source directory.
# This script is only for development.
#

SRC='./tests/fixtures/images'
DST='../../../sites/default/files/images'

#
# Urlencode function from https://gist.github.com/cdown/1163649
#
urlencode() {
    # urlencode <string>
    old_lc_collate=$LC_COLLATE
    LC_COLLATE=C

    local length="${#1}"
    for (( i = 0; i < length; i++ )); do
        local c="${1:i:1}"
        case $c in
            [a-zA-Z0-9.~_-]) printf "$c" ;;
            *) printf '%%%02X' "'$c" ;;
        esac
    done

    LC_COLLATE=$old_lc_collate
}

#
# Make sure the directory exists, then copy
#
mkdir -p ${DST} 2> /dev/null

for f in ${SRC}/*
do
  FILE=`basename "$f"`
  FILECODED=`urlencode "${FILE}"`
  cp "${f}" "${DST}/${FILECODED}"
done




