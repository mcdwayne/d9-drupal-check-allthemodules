This directory contains the build scripts for the manual:
- mkoutput.sh - builds the HTML output for use in the AsciiDoc Display Direct
  module. See https://www.drupal.org/project/asciidoc_display
- mkfeeds.sh - builds the HTML output for use in the AsciiDoc Display Feeds
  module. See https://www.drupal.org/project/asciidoc_display
- mkebooks.sh - builds e-book and PDF output.

The scripts were derived from the build scripts in:
  https://www.drupal.org/project/asciidoc_display
See that project's README.txt file for more information.

It also contains two utility scripts:
- testurls._php - tests validity of URLs in a source directory.
- search_replace._php - performs text search and replace in a source directory.

SOFTWARE

To run the scripts here, you will need the following software:
- AsciiDoc (has been tested with version 8.6.9)
- DocBook XSL and xmlto (has been tested with xmlto version 0.0.28)
- FOP (version 2.1 or later)
- Calibre

On Linux, you can try one of the following commands to install the packages:

apt-get install asciidoc docbook fop calibre
yum install asciidoc docbook fop calibre


LANGUAGES

A note on languages and configuration files: Each language has a file
lang-lc.conf, where lc is the language code. This file contains three
lines that give the translations for the words Preface, Index, and Glossary.
They need to match the corresponding chapter/section headings in the guide.txt
and glossary.txt files, in order for the document structure to build properly.

Also, to build output you need the language to be in the languages.txt file.

FONTS

In order to build PDF files, you need to have several fonts installed:
 - Noto fonts -- https://www.google.com/get/noto/
 - GNU Unifont -- http://www.unifoundry.com/unifont.html
 - Amiri -- http://www.amirifont.org/
 - BabelStone Han -- http://www.babelstone.co.uk/Fonts/Han.html
 - Takao -- https://launchpad.net/takao-fonts

On Linux, they can be found in the following packages (install with apt-get or
yum):
  fonts-noto-hinted unifont fonts-hosny-amiri fonts-babelstone-han fonts-takao
Use the links above for other operating systems. You can see which fonts
are installed on Linux by using the command:
  fc-list

When adding a new language, you may need to adjust the mkebooks.sh script so
that an appropriate font is used for the new language.
