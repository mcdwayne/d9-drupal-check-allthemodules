$Id

-- SUMMARY --

NWS_weather provides a daily weather forecast utilizing the National Weather
Service's SOAP interface. SOAP calls are cached with the standard Drupal
caching mechanism to minimize server workload.


-- REQUIREMENTS --

SOAP must be enabled in PHP.


-- INSTALLATION --

Install as usual, see http://www.drupal.org/node/70151 for further information.


-- CONFIGURATION --

Configure weather block at /admin/config/weather/nws_weather

These options should be self explanatory. Use /admin/structure/block to
enable and place the 'NWS weather forecast' block.


-- ADVANCED USAGE --

The block defined by NWS_weather allows simple access to weather forecast
data for one point. Developerswho would like to retrieve data for multiple
points or who wish to retrieve more data (wind speed, wind direction, etc.)
can utilize this module by calling this function:

$forecast=nws_weather_NDFDgen($lat, $lon, array('wdir', 'temp', 'maxt',
'mint'), "time-series", "2009-12-08T12:00", "2009-12-09T12:00");

None of the configuraton options exposed on the
/admin/config/weather/nws_weather admin page are utilized by this function.
The various parameters are discussed here:

http://www.nws.noaa.gov/xml/#use_it

The third parameter, a list of valid forecast data types, is defined in
funtion nws_weather_NDFDgen which is contained in nws_weather.module. Please
notice that the passed array is weather values only, not key/value pairs like
the array of options in the code. It is left to the developer to parse the
array that is returned.


-- DRUPAL 8 VERSION (8.x-1.x) --

All of the configuration, which had been a combination of variables and a
database table have been converted to the new Drupal 8 config system.  Therefore
you should be able to take advantage of the deployment capabilities of Config.

One of the limitations of the new Config system is that keys cannot contain dots.
Therefore the mapping from original url to an override image file has been
changed.  Instead of using the full source url as the key, such as
"http://www.nws.noaa.gov/weather/images/fcicons/sct.jpg", only the base file
name without the extension, such as "sct" from the above url,  is used as a key.
That does mean that if there were three versions of sct (i.e sct.gif, sct.jpg,
and sct.png), they will all be known by the single base name "sct" and all three
could only be mapped to a single overriden image file.


-- CONTACT --

Current maintainers:
* Dwaine Trummert (dwaine) - http://www.drupal.org/user/595304
* Tom Davidson (tomdavidson) - http://www.drupal.org/user/693092
* David Olson (David4514) - http://www.drupal.org/user/761510


This project has been sponsored by:
* DND COMMUNICATIONS
