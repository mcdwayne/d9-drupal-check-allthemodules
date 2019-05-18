Introduction - Store Locator
===========================
Store Locator module provides a simple way to add the stores and display them
in google map with configurable InfoWindow & List.


REQUIREMENTS
-------------
All dependencies of this module are enabled by default in Drupal 8.x.


INSTALLATION
-------------
Install this module as usual. Please see
http://drupal.org/documentation/install/modules-themes/modules-8


USAGE
-------
To use this module create store entity pages with configurable options:

* Add items in /store_locator/add page.
* Click on the 'Calculate Lat/Long' to get the Latitude & Longitude of the 
entered fields or click on the map to auto generate latitude & longitude.
* View the Store Locator page at /store-locator.
* Place the 'Store Locator' block anywhere by visiting /admin/structure/block.


CONFIGURATION
--------------
Global module settings can be found at /admin/config/store_locator/settings.

 * Upload Marker Icon to display the marker on the map.
 * Generate Google Map API key and add it.
  https://developers.google.com/maps/documentation/javascript/get-api-key
 * Items Visibility in List & Infowindow with sort option.

Entity Settings & List Page:
 * Store Locator Administration page /admin/structure/store_locator/settings.
 * Store Locator List page /store_locator/list.

SUPPORT
--------
Please use the issue queue to report bugs or request support:
http://drupal.org/project/issues/storelocator
https://www.drupal.org/u/vedprakash
