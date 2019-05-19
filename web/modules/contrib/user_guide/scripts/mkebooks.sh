#!/usr/bin/env bash

# This script builds PDF, ePub, and Mobi output for the guide.
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
  php preprocess._php ../source/$lang ../output/ebooks/$lang i
  cp ../source/$lang/guide-docinfo.xml ../output/ebooks/$lang

  # Run the AsciiDoc processor to convert to DocBook for ebooks.
  asciidoc -d book -b docbook -f std.conf -a docinfo -a lang=$lang $langconf -o ../output/ebooks/$lang/guide.docbook ../output/ebooks/$lang/guide.txt

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
      xmlto fo -m pdf-farsi.xsl guide.docbook

  elif [ "$lang" = "zh-hans" ]; then
      xmlto fo -m pdf-cjk.xsl guide.docbook

  elif [ "$lang" = "ja" ]; then
      xmlto fo -m pdf-takao.xsl guide.docbook

  else
      xmlto fo -m pdf.xsl guide.docbook

  fi

  # Process the FO output to remove certain characters and language attributes.
  php foprocess._php guide.fo guide.fop
  # Process this output into PDF.
  fop -c fop-conf.xml -fo guide.fop -pdf guide.pdf

  # Run the xmlto processor to convert from DocBook to ePub.
  # The syntax is:
  #   xmlto epub [input docbook file]
  # And we need to do this for the regular and mobi styles.
  xmlto epub -m epub.xsl guide.docbook
  cp guide.docbook guide-simple.docbook
  xmlto epub -m mobi.xsl guide-simple.docbook

  # Add images to the epub formats, which are actually zip files.
  mkdir -p OEBPS
  mkdir -p OEBPS/images
  cp images/* OEBPS/images
  zip guide.epub OEBPS/images/*
  zip guide-simple.epub OEBPS/images/*

  # Run the calibre processor to convert from ePub to Mobi, but on a modified
  # ePub format. Also convert to azw3 for newer Kindles and RTL languages.
  # The syntax is:
  #   ebook-convert [input epub file] [output file] [options]
  ebook-convert guide-simple.epub guide.mobi
  ebook-convert guide-simple.epub guide.azw3

  # Go back to the scripts directory to process the next language.
  cd ../../../scripts

  # Copy final output to ebooks directory.
  cp ../output/ebooks/$lang/guide.epub ../ebooks/guide-$lang.epub
  cp ../output/ebooks/$lang/guide.mobi ../ebooks/guide-$lang.mobi
  cp ../output/ebooks/$lang/guide.azw3 ../ebooks/guide-$lang.azw3
  cp ../output/ebooks/$lang/guide.pdf ../ebooks/guide-$lang.pdf

done
