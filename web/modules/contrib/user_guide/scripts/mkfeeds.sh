#!/usr/bin/env bash

# This script builds the AsciiDoc Display Feeds module output for the
# guidelines and user guide.

# Exit immediately on uninitialized variable or error, and print each command.
set -uex

# Make the output directories if they do not exist.
mkdir -p ../output
mkdir -p ../output/html_feed

# Process each language. Add new languages to the languages.txt file.
for lang in `cat languages.txt`
do

  # Make output directories.
  mkdir -p ../output/html_feed/$lang
  mkdir -p ../output/html_feed/$lang/images

  langconf=''
  if [[ -s lang-$lang.conf ]] ; then
    langconf="-f lang-$lang.conf"
  fi

  # Run the preprocessor that puts file names into the files under each header.
  php preprocess._php ../source/$lang ../output/html_feed/$lang fi
  cp ../source/$lang/guide-docinfo.xml ../output/html_feed/$lang

  # Run the AsciiDoc processor to convert to DocBook format. Syntax:
  #  asciidoc -d book -b docbook -f [config file] -o [output file] [input file]
  asciidoc -d book -b docbook -f std.conf -a docinfo -a lang=$lang $langconf -o ../output/html_feed/$lang/guide.docbook ../output/html_feed/$lang/guide.txt

  # Run the xmlto processor to convert from DocBook to bare XHTML,
  # using a custom style sheet that makes output for the AsciiDoc Display
  # Direct module, plus a chunking parameter for Feeds that outputs all
  # topics on their own pages instead of having the first one on the chapter
  # page. Syntax:
  #   xmlto -m feeds.xsl xhtml [parameters] -o [output dir] [input docbook file]
  xmlto -m feeds.xsl xhtml -o ../output/html_feed/$lang ../output/html_feed/$lang/guide.docbook

  # Copy image files to output directory.
  cp ../source/$lang/images/*.png ../output/html_feed/$lang/images

done

# Now process the Guidelines book. It has a few differences from the
# User Guide language books. See comments above in the languages section.

mkdir -p ../output/html_feed/guidelines
mkdir -p ../output/html_feed/guidelines/images

php preprocess._php ../guidelines ../output/html_feed/guidelines fi

asciidoc -d book -b docbook -f std.conf -f guidelines.conf -o ../output/html_feed/guidelines/guidelines.docbook ../output/html_feed/guidelines/guidelines.txt

xmlto -m feeds.xsl xhtml --stringparam section.autolabel.max.depth=2 -o ../output/html_feed/guidelines ../output/html_feed/guidelines/guidelines.docbook

cp ../guidelines/images/*.png ../output/html_feed/guidelines/images
