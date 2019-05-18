CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Permissions filtered by modules provides lightweight filters
for module list and roles list at the top of the Permissions
page for easier management when your site has a large
amount of roles and/or permissions.

This module don't override core path or core permissions form.
It simple provides own form with ajaxify filters for
permissions administration process.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/pfm

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/pfm


INSTALLATION
------------

 * Run composer to install the dependencies.
   composer require 'drupal/pfm:^1.0'

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.

 * A more comprehensive installation instruction for Drupal 8
   can be found at https://www.drupal.org/node/2923804/


CONFIGURATION
-------------

 * Configure user permissions in Administration » People » Permissions:

   - Use the administration pages and help (System module)

     The top-level administration categories require this permission to be
     accessible. The administration menu will be empty unless this permission
     is granted.

   - Access administration menu

     Users in roles with the "Access administration menu" permission will see
     the administration menu at the top of each page.


MAINTAINERS
-----------

Current maintainers:
 * BlacKICEUA - https://www.drupal.org/u/blackiceua
