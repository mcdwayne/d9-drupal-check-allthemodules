CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

In some case, you can have a huge editorial content and you would like navigate
into for it easier reading or highlight some sections.
In this case, usually you create a specific text field marked as hX and create
tour navigation with JS.

This module has an implementation of this generic solution plus some
configurable links on end of the navigation.

This module:
 - Adds a new field (basically an extended fieldtext) with a title and an
   anchor.
 - Adds a configuration in all fieldable entities permitting you to choose if
   you want to print the InContentNavigation for this entity
 - Adds an administration page, permitting you to add links to the Navigation

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/icn

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/icn


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the In Content Navigation module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > Content types and add a In
       Content Navigation Title to the desired content type.
    3. Optionally, you can add a default title and anchor. Save settings.
    4. Navigate to Administration > Configuration > System > In Content
       Navigation Default Links to manage content navigation.


MAINTAINERS
-----------

 *DrDam - https://www.drupal.org/u/drdam
