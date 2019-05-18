#!/bin/bash
#
# Copy test files to migration source directory.
# This script is only for development.
#

SRC='./tests/fixtures/csv'
DST='../../../sites/default/files/import'
FIXTURE=product.csv

#
# Make sure the directory exists, then copy
#
mkdir -p ${DST} 2> /dev/null
cp "${SRC}/${FIXTURE}" "${DST}"
