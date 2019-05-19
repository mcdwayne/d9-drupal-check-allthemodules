CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended Modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Views Simple Math Field module creates a Views field handler that enables
the user to perform simple math expressions based on two of the user's
view's fields.

 * For a full description of the module visit:
   https://www.drupal.org/project/views_simple_math_field

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/views_simple_math_field


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


RECOMMENDED MODULES
-------------------

Note: Twig can do math, so that may be your best option. This field is
especially useful for site-builders who want to avoid Twig or who have a
module like Charts that responds better to this module than a field rewrite.

 * Charts - https://www.drupal.org/project/charts


INSTALLATION
------------

 * Install the Views Simple Math Field module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > Views and either create or edit
       a view.
    3. Add two fields that output numbers. These will be the two values on which
       you will perform an operation.
    4. Add the "Global: Simple Math Field" field (created by this module).
       Select the two fields and the operation to perform. Apply and save.

Please note:
After you enable this module you may need to run cron/clear caches.


MAINTAINERS
-----------

 * Daniel Cothran (andileco) - https://www.drupal.org/u/andileco

Supporting organization:

 * John Snow, Inc. (JSI) - https://www.drupal.org/john-snow-inc-jsi
