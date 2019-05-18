------------------------
FREEGEOIP MODULE README
------------------------

The FreeGeoIP module is intended to enable the site developers find out the location of a visiting user. Site developers then can use this information and tweak the site functionality per region if required.
For e.g. Want to show different content at a time on site for a user in United States as compared to that of a user in India.

------------------------
INSTALLATION
------------------------
1. Download the module and place it with other contributed modules
   (e.g. sites/all/modules/).

2. Enable the FreeGeoIP module on the Modules list page. Appropriate
   tables will be created in the background.

3. Modify permissions on the People >> Permissions page.

4. Go to admin/config/services/freegeoip, and change the FreeGeoIP URL if required, i.e. if you have Redis server installed at your end.

5. Also change the Default Country if required.

6. Click Save configuration.

------------------------
INSTRUCTIONS FOR DEVELOPERS
------------------------
FreeGeoIP contacts the Redis server to find out the location from which a user is coming. This happens with the help of a http request implemented in the implementation of hook_boot(). in the module. This data is saved in the session variable. The variable can be accessed as

$_SESSION['freegeoip'];

which is an array of the format :

array(
  'ip' => '',
  'country_code' => '',
  'country_name' => '',
  'region_code' => '',
  'region_name' => '',
  'city' => '',
  'zipcode' => '',
  'latitude' => '',
  'longitude' => '',
  'metro_code' => '',
  'area_code' => '',
)
