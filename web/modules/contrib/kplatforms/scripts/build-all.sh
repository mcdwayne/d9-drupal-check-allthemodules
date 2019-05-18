#!/bin/bash
if [ -z "$DRUSH" ] ; then
  DRUSH=drush
fi

if [ -z "$DEST" ] ; then
    DEST=build
fi

make clean
for i in `ls lockfiles/*.lock` ; do
  PREFIX=`echo $i | cut -d '/' -f 2 | cut -d '.' -f 1`
  $DRUSH --concurrency=10 make "$i" "$DEST"/"$PREFIX"-"$BUILD_NUMBER"
done
