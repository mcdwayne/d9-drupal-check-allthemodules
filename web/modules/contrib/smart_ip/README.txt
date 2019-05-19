
Description:
Smart IP identify visitor's geographical location (longitude/latitude), country,
region, city and postal code based on the IP address of the user. These
information will be stored at session variable ($_SESSION) with array key
'smart_ip' and Drupal user data service using the parameters 'smart_ip' as
'module' and 'geoip_location' as 'name' of the user but optionally it can  be
disabled (by role) at Smart IP admin page. Other modules can use the function
\Drupal\smart_ip\SmartIp::query($ipAddress) that returns an array containing the
visitor's ISO 3166 2-character country code, longitude, latitude, region, city
and postal code. It provides a feature for you to perform your own IP lookup and
admin spoofing of an arbitrary IP for testing purposes.

Requirements:
Drupal 8.x
Geo Time Zone
geoip2/geoip2 library (if using MaxMind as data source)
ip2location/ip2location-php library (if using IP2Location as data source)

Installation:
1. Copy the extracted smart_ip directory to your Drupal modules/contrib
directory.
2. Login as an administrator. Enable the module at
http://www.example.com/admin/modules and also at least one Smart IP data source
module.
3. Set your private file system path at your settings.php
4. Configure/update Smart IP database/lookup an IP at
http://www.example.com/admin/config/people/smart_ip.

Support:
Please use the issue queue for filing bugs with this module at
https://www.drupal.org/project/issues/smart_ip