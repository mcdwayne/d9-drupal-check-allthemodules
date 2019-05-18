CONTENTS OF THIS FILE
--------------------

* Introduction
* Requirements
* Recommended modules
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

The Advanced Help module allows module developers to store their help outside
the module system, in pure .html or .md (MarkDown) files.  It provides a
framework that allows module and theme developers to integrate help texts in a
Drupal site, as well as exposing help to site administrators through the
administrative interface.

* For a full description of the module visit https://www.drupal.org/node/2461741

* To submit bug reports and feature suggestions, or to track changes visit
  https://www.drupal.org/project/issues/advanced_help


REQUIREMENTS
------------

This module has no required dependencies outside of Drupal core.


RECOMMENDED MODULES
-------------------

* Advanced Help Hint (https://www.drupal.org/project/advanced_help_hint):
  If Advanced Help is not enabled, this module will generate a hint string that
  can be used in the project's hook_help to hint that Advanced Help should be
  enabled.


INSTALLATION
------------

Install the Advanced Help module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/895232 for more information.


CONFIGURATION
--------------

By itself, this module doesn't do much. It assists other modules and themes in
showing help texts. Nothing will show up until you enable at least one other
module that makes use of the Advanced Help framework or comes with a file named
README.md or README.txt.


MAINTAINERS
-----------

* David Valdez - https://www.drupal.org/u/gnuget
