INTRODUCTION
------------

Provides new type of field - polygon field. With it you can create polygons
on the Google Map by clicking and dragging. Polygons are stored in Google
polyline format.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/gmap_polygon_field

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/node/add/project-issue/2877719


REQUIREMENTS
------------

This module hasn't any special requirements. It using Google Maps directly
via API, so the GMap module isn't needed.


INSTALLATION
------------

Install as usual, see
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules for further
information.


CONFIGURATION
-------------
 * Configure user permissions in Administration » People » Permissions:

   - Administer GMap Polygon Field

     This permission is required for accessing module's settings
(/admin/config/content/gmap_polygon_field).

 * On the settings page set Google Maps API key, otherwise Google Maps won't
render. You can also set there appearance of polygon - stroke color, opacity
and weight.


MAINTAINERS
-----------

Current maintainers:

 * Ondrej Linger (lingros) - https://www.drupal.org/user/3529589/
 * Jan Imrich (imrija) - https://www.drupal.org/user/3524399/
 * Vladimir Soukal (vladimir.soukal@macron.cz)
