MOBILE NUMBER
-------------

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended Modules
 * Features
 * Integration (with other modules)
 * Installation

INTRODUCTION
------------

Mobile Number is a field type that provides mobile number validation and
verification, and can be used for two factor authentication. It works with
SMS Framework and TFA modules, respectively, and both features are optional.

The module differs from the Phone module in that it puts security and
authenticity first, and focuses on mobile numbers which therefore assumes sms
and smartphone capabilities by the user. The number is sanitized before saving,
country is validated, the number is validated to be a mobile number type,
allows uniqueness validation, and, if enabled, allows verifying number
ownership by the user. For verification, codes are hashed and tokenized in the
database. See the case for a dedicated mobile number field.
(https://www.drupal.org/node/2783505)

The module uses libphonenumber (developed by Google for use in Android) for
validation, and supports the mobile number formats of the countries listed
here (https://github.com/giggsey/libphonenumber-for-php/blob/master/src/
libphonenumber/ShortNumbersRegionCodeSet.php), which is basically all of them.


REQUIREMENTS
------------

This module requires the following php library:
 * giggsey/libphonenumber-for-php (https://github.com/giggsey/libphonenumber-for-php)


RECOMMENDED MODULES
-------------------

 * SMS Framework (https://www.drupal.org/project/smsframework):
 * TFA (https://www.drupal.org/project/tfa):


FEATURES
--------

 * Mobile number validation
 * Mobile number verification
 * Uniqueness validation
 * Two factor authentication


INTEGRATION (WITH OTHER MODULES)
--------------------------------

 * Feeds
 * Telephone
 * Devel
 * TFA
 * SMS Framework


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.
 * Install the  libphonenumber project using Drupal's root composer.
 * For mobile number verification, download, enable, and configure SMS
   Framework. You will need to setup an SMS gateway, which will require
   setting up an account and paying fees on a 3rd party service, or use SMS
   Framework's default logger gateway for testing.
 * For two factor authentication, complete step 3 and download, enable, and
   configure TFA.
