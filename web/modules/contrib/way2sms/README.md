CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Module Details
 * Dependencies
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers


INTRODUCTION
------------

Way2sms module is an integration with rules module which provides free SMS
service to users in India using way2sms API.

MODULE DETAILS
--------------

This module uses an unofficial way2sms API
(https://github.com/kingster/Way2SMS-API) in which it requires a sender's
phone number which should be registered at way2sms
(http://site24.way2sms.com/) before using this module.
The same phone number and password is used to trigger the API. The
receiver's phone number(may not be registered at way2sms) and message
is configurable and can be used as per the requirements.

DEPENDENCIES
------------

* Libraries (https://www.drupal.org/project/libraries)
  This is a dependency for way2SMS inorder to check the way2SMS API.

* Rules (https://www.drupal.org/project/rules)
  This is a dependency for way2SMS as the way2SMS API is triggered with
  event, SMS action.

INSTALLATION
-------------

* Download the way2SMS module.
* Download way2SMS API from (https://github.com/kingster/Way2SMS-API)
  and place it in libraries/way2sms
  such that way2sms-api.php is available as
  libraries/way2sms/way2sms-api.php.
* Enable the module and configure login credentials(Admin Mobile number
  and password).

CONFIGURATIONS
--------------
* Configure sender's (admin) phone number and password at
  /admin/config/services/way2sms.
* Create Rule as per your requirement and add way2sms action:
  Send SMS, for the event you want the SMS to be sent.

TROUBLESHOOTING
---------------

Way2SMS works only on the correct Sender's phone number and password and
the API correctly downloaded and placed in libraries directory.
In case the module is not working, please check :
* /admin/config/services/way2sms - for correct credentials.
* way2sms libraries are not placed correctly as per described in installation.
* Check the action for which you are triggering the SMS service.
* Clear cache with change in rules.
* Reinstall the module after disable and uninstallation.

MAINTAINERS
-----------

Current maintainers:

 * Kajal Kiran (https://www.drupal.org/u/kajalkiran)
 * Anand Toshniwal (https://www.drupal.org/u/anandtoshniwal93)

This project has been sponsored by:
 * QED42
  QED42 is a web development agency focussed on helping organisations and
  individuals reach their potential, most of our work is in the space of
  publishing, e-commerce, social and enterprise.
