INTRODUCTION
------------

Geolocation Street View extends upon Geolocation Field to save and display
Street View POVs.

REQUIREMENTS
------------

This module requires the following modules:

 * Geolocation Field 8.x-1.x (https://www.drupal.org/project/geolocation)

INSTALLATION
------------

Install as you would normally install a contributed Drupal module. See
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules.

CONFIGURATION
-------------

 * Create a Geolocation field and configure both the widget and formatter to
   "Geolocation Street View".
 * Optionally, configure the formatter and disable displaying the address info
   and the Street View close button.
 * When creating field contents, go to the desired location and drop the Street
   View marker.
 * Configure the POV and save the contents.
 * Display the content. The field will show the configured Street View POV.

KNOWN ISSUES
------------

 * You can configure a default location for a field, but not a default Street
   View POV. An error is displayed upon saving. This is caused by Geolocation
   Field issue #2892320, which is not fixed in a released version.

MAINTAINERS
-----------

Current maintainers:
 * Dietrich Moerman (dietr_ch) - https://www.drupal.org/u/dietr_ch

Initial development time was provided by:
 * EntityOne - https://entityone.be/
