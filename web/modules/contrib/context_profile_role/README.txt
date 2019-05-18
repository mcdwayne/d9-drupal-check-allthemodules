CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Similar modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Context Profile Role module provides a new block condition. When viewing an
user profile (view mode full), it allows you to check if the profile has one of
the selected roles.

Use case: displaying different blocks on user profiles based on their roles.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/context_profile_role

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/2413605


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


SIMILAR MODULES
---------------

 * Context User Pages (https://www.drupal.org/project/context_user_pages) (D6)


INSTALLATION
------------

 * Install the Context Profile Role module as you would normally install a
   contributed Drupal module. Visit
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.


CONFIGURATION
-------------

The module provides a new condition plugin so it is possible to add a new
visibility condition on blocks placed using the native system or using the
Context module.

Example using the native block system:
  1. Navigate to Administration > Extend and enable the module.
  2. Navigate to Administration > Structure > Block layout and choose a block
     to edit.
  3. There is now a "User Profile Role" tab in the Visibility field set.
  4. Options include:
      * Roles
      * Select a user profile value: use "User profile from URL"
      * Negate the condition

MAINTAINERS
-----------

Current maintainer:
 * Florent Torregrosa (Grimreaper) - https://www.drupal.org/user/2388214
