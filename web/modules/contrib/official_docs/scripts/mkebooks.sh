#!/usr/bin/env bash

# This script builds PDF, ePub, and Mobi output for the guides.
# See README.txt for notes about fonts and languages.

# Exit immediately on uninitialized variable or error, and print each command.
set -uex

# Make the output directories if they do not exist.
mkdir -p ../output
mkdir -p ../output/ebooks
mkdir -p ../ebooks

# Process each language. Add new languages to the languages.txt file.
for lang in `cat languages.txt`
do

  # Make output directories.
  mkdir -p ../output/ebooks/$lang
  mkdir -p ../output/ebooks/$lang/images

  langconf=''
  if [[ -s lang-$lang.conf ]] ; then
    langconf="-f lang-$lang.conf"
  fi

  # Run the preprocessor that fixes index entries.
  php  preprocess.php ../source/$lang ../output/ebooks/$lang i
  cp ../source/$lang/guides-docinfo.xml ../output/ebooks/$lang

  # Run the AsciiDoc processor to convert to DocBook for ebooks.
  asciidoc -d book -b docbook -f std.conf -a docinfo -a lang=$lang $langconf -o ../output/ebooks/$lang/guides.docbook ../output/ebooks/$lang/guides.asciidoc

  # Copy image files and config files to e-book directory.
  cp ../source/$lang/images/*.png ../output/ebooks/$lang/images
  cp fop-conf.xml ../output/ebooks/$lang
  cp *.xsl ../output/ebooks/$lang
  cp foprocess._php ../output/ebooks/$lang

  # Run the rest of the script from the output directory.
  cd ../output/ebooks/$lang

  # Make FO intermediate file for PDF output. Which font to use depends on the
  # language.
  if [ "$lang" = "fa" ]; then
      xmlto fo -m pdf-farsi.xsl guides.docbook

  elif [ "$lang" = "zh-hans" ]; then
      xmlto fo -m pdf-cjk.xsl guides.docbook

  elif [ "$lang" = "ja" ]; then
      xmlto fo -m pdf-takao.xsl guides.docbook

  else
      xmlto fo -m pdf.xsl guides.docbook

  fi

  # Process the FO output to remove certain characters and language attributes.
  php foprocess._php guides.fo guides.fop
  # Process this output into PDF.
  fop -c fop-conf.xml -fo guides.fop -pdf guides.pdf

  # Run the xmlto processor to convert from DocBook to ePub.
  # The syntax is:
  #   xmlto epub [input docbook file]
  # And we need to do this for the regular and mobi styles.
  xmlto epub -m epub.xsl guides.docbook
  cp guides.docbook guides-simple.docbook
  xmlto epub -m mobi.xsl guides-simple.docbook

  # Add images to the epub formats, which are actually zip files.
  mkdir -p OEBPS
  mkdir -p OEBPS/images
  cp images/* OEBPS/images
  zip guides.epub OEBPS/images/*
  zip guides-simple.epub OEBPS/images/*

  # Run the calibre processor to convert from ePub to Mobi, but on a modified
  # ePub format. Also convert to azw3 for newer Kindles and RTL languages.
  # The syntax is:
  #   ebook-convert [input epub file] [output file] [options]
  ebook-convert guides-simple.epub guides.mobi
  ebook-convert guides-simple.epub guides.azw3

  # Go back to the scripts directory to process the next language.
  cd ../../../scripts

  # Copy final output to ebooks directory.
  cp ../output/ebooks/$lang/guides.epub ../ebooks/guides-$lang.epub
  cp ../output/ebooks/$lang/guides.mobi ../ebooks/guides-$lang.mobi
  cp ../output/ebooks/$lang/guides.azw3 ../ebooks/guides-$lang.azw3
  cp ../output/ebooks/$lang/guides.pdf ../ebooks/guides-$lang.pdf

done
