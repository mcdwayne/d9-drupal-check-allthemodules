
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers



INTRODUCTION
------------

With this module, you are able to assign worldwide locations to your content,
without taxonomy and manual imports.

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/location_selector



REQUIREMENTS
------------

Only a free GeoNames user account is needed (for free).
See the "Configuration" section for more information.



INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.



CONFIGURATION
-------------

 * Go to www.geonames.org/login and create a free account for limitless API
   access. See also:
   www.geonames.org/export/web-services for more information.

 * Go to /admin/config/location_selector/settings and enter the
   geonames username.

 * On "Manage Fields": Add a location selector field to your entity.
   (e.g. Content Type)

 * On "Manage Form Display": Set the Field Widget parameters.

 * On "Manage Display": Set the Field Display parameters.

 * If you want, create a view with this field as an exposed filter.

 * See more details/features on:
   https://www.drupal.org/project/location_selector



TROUBLESHOOTING
---------------

  * Why does the Select list not appear?.

    - Set your GeoNames Username at /admin/config/location_selector/settings.

  * Why does the Select list still not appear?.

    - Delete/Refresh/Rebuild the Drupal and browser cache.


MAINTAINERS
-----------

Current maintainers:
  * Chris Casutt (handkerchief) https://www.drupal.org/u/handkerchief

This project has been sponsored by:
 * Chris Casutt Realization
   Own realization agency, www.chriscasutt.ch
