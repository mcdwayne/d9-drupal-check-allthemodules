INTRODUCTION
------------

With this add-on you can log in through oauth 2.0 from the 
Greek Goverment GSIS site to authenticate the user.

gsislogin is built for Drupal 8.

If the user does not exist, then they are created to the system, 
otherwise will be logged in directly.
The username of the user is the same as the userid returning from GSIS service.

In the service settings for the allowed url you must add the url of the gsis
e.g. https://www.example.com/gsis

INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. Visit:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


Login and registration forms get an icon to log in thrugh GSIS,
alternatively, you can use the form in /gsis/login 
or direct route the user with your own way to /gsis

Adds to user fields:
field_gsis_userid - The username
field_gsis_taxid - VAT number
field_gsis_lastname - Surname
field_gsis_firstname - The name
field_gsis_mothername - Mother name
field_gsis_fathername - The name Father
field_gsis_birthyear - The Birth Time

to store the items coming from the GSIS service

CAUTION in case of uninstallation this fields are deleted!


REQUIREMENTS
------------

No special requirements


CONFIGURATION
-------------

Configure oauth 2 id and key provided from GSIS service in 
Administration » Config » People » GSIS oauth2 Login
You must have a role "administer site configuration" to access this form.

MAINTAINERS
-----------

Current maintainer:
 * Panagiotis Skarvelis (sl45sms) - https://www.drupal.org/u/sl45sms
