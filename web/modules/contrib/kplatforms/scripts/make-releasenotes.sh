#!/bin/bash
# Create release notes

if [ -z $DRUSH ] ; then
  DRUSH=drush
fi
STUBS_DIR=./stubs
LOCK_DIR=./lockfiles
BUILD_DIR=./build
for i in `ls $BUILD_DIR/*.txt`; do
  TITLE=`echo "$i" | cut -d '/' -f 3 | cut -d '.' -f 1`
  echo "<h2>$TITLE</h2>"
  echo "<pre>"
  cat $i
  echo -e "</pre>\n\n"
done;
