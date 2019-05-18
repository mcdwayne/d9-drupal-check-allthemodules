CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Dynamic Banner is a module that lightens the load on web developers from
creating many blocks for pages with different banners.

This module will read from the database to automatically decide which banner
goes on which page based off of the rules an administrator sets.

This module has many different usage patterns and is extremely reusable.

 * For a full description of the module visit:
   https://www.drupal.org/project/dynamic_banner

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/dynamic_banner


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Dynamic Banner module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > Dynamic Banners to add
       banners.
    3. Select "Add Banner" to add a new banner.
    4. Enter Banner Path by specifying an existing url path you wish to put a
       banner on. For example: home, user* (wild card), content! (random). Enter
       a path as it appears in the url of your site.
    5. Choose an image type, upload an image, enter the text associated with the
       banner, and specify the link to point to.
    6. Select the mode for the banner to display under (this is different than
       display setting): normal, time_based, rotating, or fade.
    7. Save Banner.


MAINTAINERS
-----------

 * abrlam - https://www.drupal.org/u/abrlam
