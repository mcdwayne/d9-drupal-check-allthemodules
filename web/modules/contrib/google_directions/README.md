
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installing
 * Configuration
 * Troubleshooting
 * FAQ
 * How Can You Contribute?
 * Maintainers & Credits


INTRODUCTION
------------

The Google Directions module integrates the Google Directions Service
to provide a user interface in Drupal for the display of directions
between two locations.

Google Directions Service is a service of the Google Maps JavaScript API.

REQUIREMENTS
--------------

A Google API Key is required to access the Google Directions Service.

If you are new to Google APIs, log in to Google Visit
https://console.developers.google.com with a Google account.

In the Credentials tab, Create an API Key.

In the API Manager tab, add the APIs:

 * Google Maps Directions API
 * Google Maps JavaScript API
 * Google Places API Web Service
 * Google Maps Geocoding API

INSTALLING
----------

* Install as you would normally install a contributed Drupal module.
See:
  https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
  for further information.


CONFIGURATION
------------

* Configure user permissions in Administration » People » Permissions:

- Administer Google Directions

* Once Google Directions is installed and enabled, and permission are granted:

1) Visit /admin/config/services/google-directions to enter your Google API Key.
2) Use the Block layout page (/admin/structure/block) to position the block
in one or more regions to suit your use case for the Google Directions module.
3) For each block placement, configure Visibility and other block settings
to suit your use case.


TROUBLESHOOTING
---------------

* If the menu does not display, flush caches and try again.

FAQ
---

- There are no frequently asked questions at this time.



HOW CAN YOU CONTRIBUTE?
-----------------------

 * Report any bugs, feature requests, etc. in the issue tracker.
  http://drupal.org/project/issues/google_directions

 * Write a review for this module at drupalmodules.com.
  http://drupalmodules.com/module/google-directions

MAINTAINERS & CREDITS
---------------------

 * Current Maintainer: Renuka Kulkarni https://www.drupal.org/u/renukakulkarni
 * Co-maintainer/Project Mngr: Mike Brooks https://www.drupal.org/u/mikebrooks
 * Original Author: Rajiv Mandve https://www.drupal.org/u/rajivmandve

This module was originally conceived for usage in the
website https://www.cttransit.com/.
The maintainers would like to acknowledge CTtransit
for being a Drupal 8 pioneer.
