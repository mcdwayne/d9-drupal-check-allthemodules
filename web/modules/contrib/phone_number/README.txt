Phone Number
-------------

Phone Number is a field type that provides flexible validation and intelligent
storage of international telephone numbers through an intuitive UI.  Users need
not enter their country-code - there's a nice little country-selector for that.
Telephone numbers of all types are supported, and type validation can be
configured per field-instance.  Phone Number fields also support (optionally)
collecting and storing extension along with the main phone number.

Validation is done using libphonenumber-for-php, the PHP version of Google's
library for parsing, formatting, and validating international phone numbers.
See https://github.com/giggsey/libphonenumber-for-php for more information.

Also included is the sub-module SMS Phone Number.  SMS Phone Number exposes a
phone number field with SMS-capabilities.  SMS phone number fields integrate
with the SMS Framework and Two-factor Authentication (TFA) modules.  When paired
with the SMS Framework module, SMS Phone Number offers in-line number
verification via an intuitive, ajax-powered field widget.  Users can enter and
verify ownership of their SMS-capable phone number in one step.  Verification
codes are hashed and tokenized in the database.

This module is a fork of the excellent Mobile Number module
(https://www.drupal.org/project/mobile_number), and aims to be a more
flexible solution, supporting international telephone numbers of all types, with
or without SMS-capabilities.


Integrates with:
----------------

 - Feeds
 - Migrate
 - Telephone
 - Devel
 - SMS Framework
 - TFA


Installation:
-------------

 - Using composer, install as you would normally install a contributed Drupal
   module.
 - For SMS number verification, install the included SMS Phone Number
   sub-module.  Then install and configure the SMS Framework module with an SMS
   gateway.
 - For two factor authentication, complete step two and then install the
   Two-factor Authentication (TFA) module.
