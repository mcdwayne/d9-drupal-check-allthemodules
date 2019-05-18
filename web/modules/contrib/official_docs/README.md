The files in this project are used to build Drupal's Official Documentation guides.

The source uses AsciiDoc markdown format, which can be compiled into DocBook format, which in turn can be compiled into HTML, PDF, and various e-book formats. The compiled documentation is imported into Drupal.org and rendered for display via the [AsciiDoc Display module](https://www.drupal.org/project/asciidoc_display).

See also the [User Guide project](https://www.drupal.org/project/user_guide), which provides a tutorial-style guide to Drupal for beginners.

COPYRIGHT AND LICENSE
---------------------

See the ASSETS.yml file in this directory, and files it references, for copyright and license information for the text source files and images in this project. Output files produced and displayed by this project also must contain copyright/license information.

Code in this project, consisting of scripts for generating output from the source files, and code for generating automatic screen captures, is licensed under the GNU/GPL version 2 and later license.

FILE ORGANIZATION
-----------------

This project contains the following directories:

* source

The AsciiDoc source for the guides is in language subdirectories of the source directory. The index file is called "guides.txt", and it has include statements for the other files that make up the guides.

* guidelines / templates

Guidelines and templates for contributors to this project are in the guidelines and templates directories, respectively. The guidelines are in the form of another AsciiDoc manual, with guidelines.txt as the index file. There are separate templates for topics covering tasks and concepts.

* scripts / output

To build both the Guides and Guidelines books, use the scripts in the scripts directory; see below for more information. Currently the Guidelines document scripts only produce HTML output for the AsciiDoc Display module, and the Guides scripts produce HTML output as well as PDF and other e-books.

The output the scripts produces lands in the output directory. Subdirectories html and html_feed of that is the output for the AsciiDoc Display module; e-books land in the ebooks subdirectory.