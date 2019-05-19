Description:
API that determines the time zone based on geo location (coordinates or
region or country). Using "geotimezone_query($location, $format);" that
returns time zone identifier or UTC/GMT offset or both (depends on $format
value). Where the first parameter is an associative array with array keys of:
longitude
latitude
regionCode
region
countryCode
To determine the time zone based on coordinates, the 'longitude' and
'latitude' keys are required to have valid coordinates values.
To determine the time zone based on region, the 'countryCode' and either of
'regionCode' or 'region' are required.
To determine the time zone based on country, the 'countryCode' is required.
If all keys are present, the coordinates (longitude/latitude) are the query's
priority (the rest will be ignored). The region is the next query's priority
if coordinates are not present. And the country is the last priority if
coordinates and region are not present.
It is recommended to provide coordinates for accurate time zone while region
is the next accurate and the least is the country where it may return
multiple time zones in array.

This module does not need web services, data files or SQL database. This is a
Drupal version of LatLongToTimezone project
(https://github.com/drtimcooper/LatLongToTimezone)

Requirements:
PHP 5.4 and up
Drupal 7.x

Installation:
1. Copy the extracted geotimezone directory to your Drupal sites/all/modules directory.
2. Login as an administrator. Enable the module at http://www.example.com/?q=admin/modules

Support:
Please use the issue queue for filing bugs with this module at
http://drupal.org/project/issues/geotimezone
