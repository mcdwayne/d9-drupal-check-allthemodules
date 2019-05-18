CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module can provide a custom 403, 404, 401 contents based on the path.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/custom_4xx_pages

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/custom_4xx_pages


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Custom 4XX Pages module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > Custom 4xx Pages to add custom
       4xx pages.
    3. Select "Add custom 4xx page" and enter the type of 4xx page: 403 - Access
       Denied, 404 - Not Found, or 401 - Unauthorized.
    4. Enter the path to apply to.
    5. Enter the path of the custom 403 page. The path can be any entity. It
       will render this entities contents in place of the standard 403 contents.
    5. Save.

Please note:
The "Path To Custom 403 Page" will attempt to use the Entity API to render
whatever it can find at that path. Node Content Type entities should definitely
work. Custom entities should also work, assuming you've built them with a
render / view mode.

As of right now, there's no real weighting involved, so the custom 4xx are
evaluated by first come first serve. That means, if I created another custom 4xx
for /members/foo, the wildcard one we used above would most likely apply first.


MAINTAINERS
-----------

 * Tyler Fahey (twfahey) - https://www.drupal.org/u/twfahey

Supporting organizations:

 * University of Texas at Austin -
   https://www.drupal.org/university-of-texas-at-austin
