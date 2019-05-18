CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------
This module provides permissions for managing regions.

The following permissions are added:

- Administer permissions for each theme's region.
  This enables granting a user permission to manage certain regions under each
  theme in the following ways:
  - Can see region's header, message, and blocks on block layout page
  - Can see region in region selector fields on block layout page
  - Can see region in region selector field on configure and place block pages
  - Can update or delete blocks placed in region

REQUIREMENTS
------------

No requirements for this module.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------

After enabling this module users without the specific permissions will not be
able to manage regions.

Assign the permissions per theme and region to each role you wish.

MAINTAINERS
-----------

Current maintainers:
 * Joshua Roberson - https://www.drupal.org/u/joshuaroberson
