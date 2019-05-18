#!/bin/bash
#
# Simple script to run the migrations in order.
# This script is only for development.
#

SRC='./tests/fixtures/images'
DST='../../../sites/default/files/images'

MIGRATIONS='
import_taxonomy_term
import_attribute_value
import_image
import_paragraph_tab
import_paragraph_cta
import_paragraph_product_video
import_product_variation
import_product'

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
# Copy test files to migration source directory.
#
mkdir -p ${DST} 2> /dev/null

for f in ${SRC}/*
do
  FILE=`basename "$f"`
  FILECODED=`urlencode "${FILE}"`
  cp "${f}" "${DST}/${FILECODED}"
done

#
#  Run the migrations.
#
for MIGRATION in ${MIGRATIONS}
do
  drush mim ${MIGRATION}
done


