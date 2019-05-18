# Drupal 8 Backstop.JS Configuration Generator

INTRODUCTION
------------

This Drupal 8 module exposes an administrative configuration form for
creating a Backstop.js visual regression testing profile based on the
Drupal site's content. You can:

- Create backstop scenarios from Drupal pages
- Define a number of random pages to be included as scenarios
- Toggle on and off viewport sizes

The resulting backstop.json file only needs to be placed into a backstop
directory (created when running `backstop init`), replacing the existing
backstop.json file.

REQUIREMENTS
------------

This module requires the following modules:

 * Serialization
 * HAL
 * REST

Additionally, this module requires the use of Backstop.JS. Visit:
https://github.com/garris/BackstopJS for more information and
installation instructions.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------

 * Create backstop generator settings at Administration » Configuration »
   Configure Backstop Generator:

 * Choose viewports

 * Use the autocomplete fields to choose pages as testing scenarios.

 * Optionally, choose a number of random additional pages to be generated
     as testing scenarios.

 * Hit 'Save and view' to review and download your configuration file.

MAINTAINERS
-----------

Current maintainers:
 * Ryan Bateman (porkloin) - https://www.drupal.org/user/3436113

This project has been sponsored by:
 * Hook 42
   Strategic web consulting and development based in the SF Bay Area, CA,
   working with Enterprise CMS integration including Drupal. Visit
   https://www.hook42.com for more information.
