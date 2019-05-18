#!/bin/sh

set -e
set -x

SIZE="48x48"
COLORS=64

find svg -name "*.svg" |
while read file;
do
  PNGFILE="$(basename "$file" .svg).png"
  convert -background transparent -resize $SIZE "$file" "$PNGFILE"

  PNGINDEXFILE="$(basename "$file" .svg)-fs8.png"
  rm -f "$PNGINDEXFILE"
  pngquant $COLORS "$PNGFILE"
  mv "$PNGINDEXFILE" "$PNGFILE"
done
