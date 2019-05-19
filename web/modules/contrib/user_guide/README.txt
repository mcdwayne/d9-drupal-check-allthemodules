The files in this project can be used to build a User Guide to Drupal. The
source uses AsciiDoc markdown format, which can be compiled into DocBook format,
which in turn can be compiled into HTML, PDF, and various e-book formats. The
AsciiDoc Display module (https://www.drupal.org/project/asciidoc_display) can be
used to display special HTML output in a Drupal site. The companion User
Guide Tests module (https://www.drupal.org/project/user_guide_tests) contains
automated tests that also generate screenshots for the User Guide.


COPYRIGHT AND LICENSE
---------------------

See the ASSETS.yml file in this directory, and files it references, for
copyright and license information for the text source files and images in this
project. Output files produced and displayed by this project also must contain
copyright/license information.

Code in this project, consisting of scripts for generating output from the
source files, is licensed under the GNU/GPL version 2 and later license.


FILE ORGANIZATION
-----------------

This project contains the following directories:

* source

The AsciiDoc source for the manual is in language subdirectories of the source
directory. The index file is called "guide.txt", and it has include statements
for the other files that make up the manual.

* assets

Images and text for use in making screen shots for the guide.

* guidelines / templates

Guidelines and templates for contributors to this project are in the guidelines
and templates directories, respectively. The guidelines are in the form of
another AsciiDoc manual, with guidelines.txt as the index file. There are
separate templates for topics covering tasks and concepts.

* scripts / output

To build both the User Guide and Guidelines, use the scripts in the scripts
directory; see below for more information.  Currently the Guidelines document
scripts only produce HTML output for the AsciiDoc Display module, and the User
Guide scripts produce HTML output as well as PDF and other e-books.

The output the scripts produces lands in the output directory. Subdirectory html
of that is the output for the AsciiDoc Display module; e-books land in the
ebooks subdirectory.


ASCIIDOC OUTPUT BUILD SCRIPTS
-----------------------------

The Guide and Guidelines are both set up with scripts to make output compatible
with the AsciiDoc Display module
(https://www.drupal.org/project/asciidoc_display), as well as PDF and other
e-book output (the scripts are adapted from the sample scripts in that project,
and only the Guide is currently set up to make e-book output).

To run the scripts/mkoutput.sh script, you will need several open-source tools:
- AsciiDoc (for any output): http://asciidoc.org/INSTALL.html
- DocBook (for any output): http://docbook.org or
  http://www.dpawson.co.uk/docbook/tools.html
- FOP (for PDF): http://xmlgraphics.apache.org/fop/
- Calibre (for MOBI): http://calibre-ebook.com/

On a Linux machine, you can use one of these commands to install all the tools:
  apt-get install asciidoc docbook fop calibre
  yum install asciidoc docbook fop calibre

On a Mac:
  brew install asciidoc xmlto
  echo "export XML_CATALOG_FILES=/usr/local/etc/xml/catalog" >> ~/.bash_profile
  source ~/.bash_profile

Note that these scripts do not work with all available versions of AsciiDoc
and Docbook tools. They have been tested to work with:
asciidoc - version 8.6.9
xmlto - version 0.0.25

You can check versions by typing
  asciidoc --version
  xmlto --version
on the command line.
