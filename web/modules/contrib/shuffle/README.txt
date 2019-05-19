CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers

INTRODUCTION
------------

Shuffle module provide a Drupal integration with the jquery library Shuffle.
This module provide :

* A views style plugin for displaying rows in a shuffle grid
* A field formatter for displaying images attached to an entity
  in a shuffle grid


REQUIREMENTS
------------

Shuffle module works with the jquery shuffle library version 3.1.1. Others
versions don't have been tested.

Required modules

 * libraries 8.x-3.x

RECOMMENDED MODULES
-------------------

The masonry API module (https://www.drupal.org/project/masonry) provides
similar features, based on the JQuery Masonry plugin
(http://masonry.desandro.com/).
Shuffle provides some additionnal features as the possibility to filter, sort
and search inside a shuffle grid.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.
 * Download the library Shuffle (http://vestride.github.io/Shuffle/) and place
   the following file jquery.shuffle.modernizr.min.js under libraries/shuffle
   folder. This file is available with the version 3.1.1. You can download it
   here : https://github.com/Vestride/Shuffle/releases/tag/v3.1.1 (folder dist)
   The complete path of shuffle file attended is :
   - libraries/shuffle/jquery.shuffle.modernizr.min.js
 * This module use libraries API (as dependency) so the attended file can be
   found too in the paths below
   - /libraries
   - /profiles/*/libraries
   - /site/all/libraries
   - /site/*/libraries
 * Check the the status report


CONFIGURATION
-------------

* Select the Shuffle formatter in the manage display page of your entity.
  This formatter is available for field of type images only
* Select the Shuffle grid views style plugin for your view and
  configure it as you need

This module provides too an integration with the magnific popup library
for the field formatter. It permit to display all images inside
a shuffle grid and to view a larger version of theses images
inside a magnific popup. The alt attribute of images is used for caption.

In order to have these options for using magnific popup, you have to donwload
the files jquery.magnific-popup.min.js and magnific-popup.css in the
folder /libraries/magnific-popup. The download url is : 
https://github.com/dimsemenov/Magnific-Popup/releases/tag/1.0.1

Files must be found in these path

- /libraries/magnific-popup/jquery.magnific-popup.min.js
- /libraries/magnific-popup/magnific-popup.css

For more advanced customization, you can override the Twig templates
provided by the module, by copying them in your folder theme.

- field-shuffle.html.twig
- views-view-shuffle.html.twig

Enable Twig debug for seeing all the available suggestions.

Using filter in a shuffle grid
You can configure a shuffle filter in the views's settings form with any
entity_reference field available with the entity, or by using the title
attributes of img in the field formatter.

TROUBLESHOOTING
---------------


FAQ
---


MAINTAINERS
-----------

Current maintainers:
 * flocondetoile - https://drupal.org/u/flocondetoile
