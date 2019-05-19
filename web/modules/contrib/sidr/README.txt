CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module allows the admin to create "trigger" blocks which when clicked, use
the Sidr libraries to slide in / slide out a specified target element. This is
very useful for implementing responsive menus. All you have to do is create one
or more dedicated regions in your theme, say, `mobile_menu` and then configure
a Sidr trigger block to toggle it.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/sidr

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/search/sidr

 * To see a changelog / commit history see the Git history page:
   https://cgit.drupalcode.org/sidr/log/


REQUIREMENTS
------------

The module requires the Sidr library (https://github.com/artberri/sidr/releases)
version 2.2.1.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
   for further information.

 * Download sidr 2.2.1 libraries from 
   https://github.com/artberri/sidr/releases/tag/v2.2.1.

 * Copy the dist directory of the download in your Drupal install such that 
   jquery.sidr.js is located in DRUPAL_ROOT/libraries/sidr/jquery.sidr.js.


CONFIGURATION
-------------

 * Go to the "Block layout" page (admin/structure/block):

   - Click on the "Place block" button for the region in which you want to
     place the trigger for your Sidr and place a "Sidr trigger button" block.
     This trigger will toggle your Sidr. Configure the block as per your needs
     and save your changes.

   - The Sidr trigger should be visible on your site and if you click on the
     trigger, you should see a Sidr menu sliding out as per your configuration.


MAINTAINERS
-----------

Current maintainers:

 * Jigar Mehta (jigarius) - https://www.drupal.org/u/jigarius
