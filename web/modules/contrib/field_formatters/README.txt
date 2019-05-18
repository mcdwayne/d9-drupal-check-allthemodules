CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Requirements
 * Installation

INTRODUCTION
------------

Define field formatters that can only be used on text
fields. These field formatters allow the site builder to choose
from one of several ways to manipulate the text field’s values before
outputting the text on the page.


FEATURES:
---------

1. Rot13 - The text field value should be printed with the ROT13 encoding
( https://en.wikipedia.org/wiki/ROT13 ). For example, “Lorem Ipsum” would become “Yberz
Vcfhz”.

2. Slugify - The text field value should be converted into a slug.
The user can to specify the separator in the field formatter settings form.

3. Tooltip - Show a tooltip on hover of the outputted text.


REQUIREMENTS
------------

The qTip2 library in "libraries" folder.
The cocur/slugify PHP library.


INSTALLATION
------------

1. Install the module as normal, see link for instructions.
   Link: https://www.drupal.org/documentation/install/modules-themes/modules-8

2. Download and unpack the qTip2 library in "libraries" folder.
    Replace the folder name by: qtip2.

    Make sure the path to the plugin file becomes:

    "libraries/qtip2/dist/jquery.qtip.min.js"
    "libraries/qtip2/dist/jquery.qtip.min.css"

   Link: https://github.com/qTip2/qTip2/archive/master.zip



