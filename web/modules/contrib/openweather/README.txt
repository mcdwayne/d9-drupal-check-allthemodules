CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Module Details
 * Recommended modules
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers


INTRODUCTION
------------
Open Weather is a module  to parse out the weather response from
openweathermap.org. The main reason we need this module because Google had
killed it's weather API, thus rendering the Google Weather module useless. This
module is a nice way for users to get weather in their site now.

MODULE DETAILS
--------------

Open weather has a few nice features.
1> It can handle unlimited cities for
weather output. Each city will have it's own block which can be customised to
show whatever weather details you see fit. You can even theme this yourself by
overriding the theme that comes with the module.
2> It also provides the token option of users so that you can make use of their
location.
3> It will also show the forecast on hourly basis with 3 hours of interval.
4> It also provides the data for daily forecast in your site, or simply display
the data anyway you want.


RECOMMENDED MODULES
-------------------

* TOKEN (https://www.drupal.org/project/token)
When enabled user tokens would be available in as an input value for the
selcted input option for open weather block.

CONFIGURATION
-------------
* The APPID which is required to make use of this module can be get from
openweathermap.org and it is configurable and corresponding configuration
settings are available at admin/config/services/openweather.Enter your APPID
over here and if the APPID entered is wrong you can get an error which you can
see under dblog.

* To use this module you can go to admin/structure/block and click on place
block where you can find a bolck called Open Weather Block.

* You can find the weather status using following types of input field.
City name
City Id
Zip Code
Geographic Coordinates
Select any one of the input option and place the value corresponding to selcted
input option in text field.

* You can also take the details of current user and make use of it for that you
need to add the field under admin/config/people/accounts/fields for the users.

* If Token module exists then you can make use of the details stored for the
user and place the token in text field for example: if you have added a field
called city name for an user and if data is stored for that user then you can
find that token available under current user menu and you can place a
placeholder like [current-user:field_city_name] where field_city_name is the
machine name of the field created for the user.

* There will be an option for the number of count which is appicable for two
condition either you want to display the forecast for hourly basis or daily
basis. In case of hourlyforecast maximum value should be 36 and in case of daily
forecast maximum value should be 7.

* You can select an option for what kinf od data you want to display like
current weather detail or forecast.

* You can also select all types of data you want to display in that block and it
is configurable.

* Warning Message to be shown, if the text field or count field will be empty.


TROUBLESHOOTING
---------------
As of now, Open weather module only works if you have used right APPID from
openweathermap.org otherwise it will show an error an error in dblog (if devel
module is enabled).
In case the module is not working properly, you may try:
* Clear the cache
* Reinstall the module after disable and uninstallation.


FAQ
---

Q: Does it work without APPID?

A: NO, you must have APPID from openweathermap.org. I would recommend you
download the devel module (https://www.drupal.org/project/devel) and you will
able the see the error in dblog only for development purpose.


MAINTAINERS
-----------
Current maintainers:

 * Prashant Kumar (https://www.drupal.org/user/3380762)


This project has been sponsored by:
 * QED42
  QED42 is a web development agency focussed on helping organisations and
  individuals reach their potential, most of our work is in the space of
  publishing, e-commerce, social and enterprise.
