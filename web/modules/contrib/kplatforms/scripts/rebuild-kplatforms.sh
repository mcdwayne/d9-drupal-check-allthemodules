#!/bin/bash

if [ -z $DRUSH ] ; then
  DRUSH=drush
fi
STUBS_DIR=./stubs
LOCK_DIR=./lockfiles
for i in `ls $STUBS_DIR`; do
  echo "Processing $i..."
  PREFIX=`cut -d "." -f 1 - <<< "$i"`
  if [ -z $PREFIX ] ; then
    echo -n " no prefix, skipped." 
    continue;
  fi
  DRUSH=$DRUSH PLATFORM=$PREFIX make build-platform
done;
