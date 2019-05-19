INTRODUCTION
------------

The Text Marketer Contacts module sends mobile numbers from any Drupal site to
Text Marketer upon user registration and user profile update. Using Text
Marketer API, it also imports mobile numbers from Text Marketer and generates
a list of user details with matching numbers on a Drupal site. You can download
the user details list with a click of a button in CSV format which can also be
uploaded to Text Marketer.

You need Text Marketer Contacts if you want to send SMS messages to your site
users from your Text Marketer account.

If you want to send SMS from your site you need Text Marketer SMS Integration
(https://www.drupal.org/project/sms_textmarketer).


FEATURES
------------

 * Supports fields created with Profile 2 (Drupal 7.x only).

 * Use an existing telephone number field or create a new field in the account
   settings page and configure Text Marketer Contacts to use it.

 * Optionally have a 'Subscribe to our SMS' checkbox. Just create a boolean
   field type in the account settings page and configure Text Marketer
   Contacts to use it.

 * Import mobile numbers from Text Marketer and display them on your site
   together with details of matching users.

 * Download a CSV file of the generated user list.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/textmarketer_contacts

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/text_marketer_contacts


REQUIRED MODULES (Drupal 7.x only)
-------------------

 * Registry Autoload (https://www.drupal.org/project/registry_autoload)
   Required for loading classes.

RECOMMENDED MODULES
-------------------

 * While not a requirement, Telephone (https://www.drupal.org/project/telephone)
   provides enhanced field validation of user mobile numbers.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.

CONFIGURATION
-------------
 * Customize the Text Marketer settings in Administration » Configuration »
   Web services » TextMarketer contacts.


TROUBLESHOOTING
---------------
 * If you ever have problems, make sure you check the log messages of your
   site as this module tries to log all failing events.
