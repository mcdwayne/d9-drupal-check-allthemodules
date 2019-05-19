CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers
 

INTRODUCTION
------------

This is a module that extends the code telephone module by including
the ability to select a type of telephone number (ie Cell, Work, Fax),
and also validated the number.  It uses libphonenumber-php library 
(port of google's libphonenumber library).

The module stores the telephone in number only format, displays the number
 in "National" (PhoneNumberFormat::NATIONAL)format, and links the number in 
 RFC3966 format (PhoneNumberFormat::RFC3966). Fax numbers are displayed as
 markup, using National format.


REQUIREMENTS
------------

- telephone
- field_ui


INSTALLATION
------------

Telephone_type must be installed via Composer, in order to get the required
 libraries. The tarballs are provided for informative purposes only.

1. Add the Drupal.org repository

composer config repositories.drupal composer https://packages.drupal.org/8
This allows Composer to find Address and the other Drupal modules.

2. Download Telephone_type

composer require "drupal/telephone_type"
This will download the latest release of telephone_type.

See <a href="https://www.drupal.org/node/2404989">Using Composer</a> in a
 Drupal project for more information.


CONFIGURATION
-------------

Configurations of the field can be found on the "Manage form display" and 
"Manage display" settings tabs for the content type you added the field to.


MAINTANINERS
------------

Current Maintainer: Scott E Worthington https://www.drupal.org/u/seworthi
