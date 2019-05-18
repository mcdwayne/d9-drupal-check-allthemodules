CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers

INTRODUCTION
------------
Provides a widget for Drupal text fields with autocomplete suggestions from
the Google Places API.
Also you can use the autocomplete path it defines for your own FAPI
implementation.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/places_api_autocomplete
   OR
   https://github.com/dreamproduction/places_api_autocomplete

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/places_api_autocomplete
   OR
   https://github.com/dreamproduction/places_api_autocomplete/issues

   Before submitting a new issue, please use the search function to check if
   your problem hasn't already been reported.

REQUIREMENTS
------------
This module requires the php cURL Library (http://php.net/manual/en/book.curl.
php).

INSTALLATION
------------
 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.

CONFIGURATION
-------------
 * Customize the module settings in Administration » Configuration »
   Web services » Google Places API.

 * To get a Google API key
  1. Go to https://code.google.com/apis/console/
  2. Create a new project
  3. Enable Google Places API
  4. Go to Credentials
  5. Create New Key
  6. Select Server key
  7. Copy the API key.
  8. Paste it in the module configuration page, at admin/config/services/places

TROUBLESHOOTING
---------------
 * If the suggestions do not appear, check the following:

   - Is the Google API key saved corectly at admin/config/services/places?

   - Is the Google Places Autocomplete widget used by the field?

FAQ
---
Q: What are the Parameters on the settings page?

A: The Google Places Api requests can use parameters to restrict the results.
On the settings page you can choose values for these parameters for the case
where the autocomplete path is used directly, without a field. For more
information about what parameters are available, and what are the possible
values for them, see:
https://developers.google.com/places/webservice/autocomplete#place_autocomplete_requests

MAINTAINERS
-----------
Current maintainers:
 * Calin Marian (mariancalinro) - https://drupal.org/user/1248398
 * Adina Chimniuc (adinac) - https://drupal.org/user/1398222
 * Bogdan Racz (rbmboogie) - https://drupal.org/user/1099612
 * Iulia Nalatan (iulia-na) - https://drupal.org/user/3227685


This project has been sponsored by:
 * Dream Production
   We love WordPress and Drupal projects. We’re great at developing custom web
   applications, and we’re just as skilled in implementing interfaces using
   HTML, CSS, and jQuery. Visit http://dreamproduction.com for more
   information.
