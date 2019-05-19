Tracking Number
Copyright 2017 Chris Caldwell <chrisolof@gmail.com>

Description
-----------
This module provides a field for storage and display of tracking numbers.

Features
--------
* Store tracking numbers for:
  - United States Postal Service
  - UPS
  - FedEx
  - DHL
  - DHL Global
  - Others by adding additional TrackingNumberType plugins
* Display your tracking numbers as either:
  - A link where tracking information for the tracking number can be found.
  - Plain text.
* Optionally display the tracking number type (following the number)
  - eg. "<a href="#">ABC123</a> (United States Postal Service)"

Configuration
-------------
To use this module, attach a field of type "Tracking number" to an entity of
your choosing.  Under the entity's display settings, configure the field to
either print out as plain text or as a link to tracking information, and
indicate whether or not you want the tracking number's type displayed after the
number itself.

To enable more tracking number types, simply add additional TrackingNumberType
plugins.  See Drupal\tracking_number\Plugin\TrackingNumberType\Usps for a
working example.

To alter, override, or remove existing tracking number types, implement
hook_tracking_number_type_info_alter() (details in tracking_number.api.php).

Bugs, Features, & Patches
-------------------------
If you wish to report bugs, add feature requests, or submit patches, you can do
so on the project page on Drupal.org.
https://www.drupal.org/project/tracking_number


Author
------
Chris Caldwell (https://www.drupal.org/u/chrisolof) <chrisolof@gmail.com>

The author can be contacted for paid customizations to this and other modules.
