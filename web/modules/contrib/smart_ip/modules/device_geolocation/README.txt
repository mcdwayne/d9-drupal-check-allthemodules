
Description:
Provides visitor's geographical location using client device location source
that implements W3C Geolocation API whereas the coordinates are geocoded using
Google Geocoding service. Google Geocoding returns a more detailed location
information such as: street number, postal code, route, neighborhood, locality,
sublocality, establishment, administrative area level 1, administrative area
level 2, etc.

Smart IP is the last fallback if W3C Geolocation API failed. Even if the
visitors refuses to share their location, the geographical information provided
by Smart IP will be used to know your visitors' geolocation details. A
themeable Block content is available to show your visitor's geolocation
information. Device Geolocation merges its location data (collected at Google
Geocoding service) with Smart IP visitor's location data storage which is in
session variable ($_SESSION) with array key 'smart_ip' and Drupal user data
service using the parameters 'smart_ip' as 'module' and 'geoip_location' as
'name' of the user.

Requirements:
Drupal 8.x
Smart IP
Google map API key

Installation:
1. Copy the extracted device_geolocation directory to your Drupal
modules/contrib directory.
2. Login as an administrator. Enable the Smart IP module first at
http://www.example.com/admin/modules and followed by Device Geolocation module.
3. Login to your Google account and get your Google map API key here: 
https://developers.google.com/maps/documentation/javascript/get-api-key
4. Configure/update Device Geolocation settings at
http://www.example.com/admin/config/people/smart_ip.
5. Configure your visitor's geolocation details block at
http://www.example.com/admin/structure/block

Support:
Please use the issue queue for filing bugs with this module at
https://www.drupal.org/project/issues/smart_ip