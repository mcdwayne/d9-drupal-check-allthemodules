#@codingStandardsIgnoreFile

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers


INTRODUCTION
------------

Provides a turn-key solution for exporting users in several different formats
included Excel, CSV, JSON, tab-delimitted, and XML.


REQUIREMENTS
------------

No special requirements


INSTALLATION
------------

Install as you would normally install a contributed Drupal module. See:
https://drupal.org/documentation/install/modules-themes/modules-8 for further
information.


CONFIGURATION
-------------

 * The functions of this module are given to users with the correct permissions.
   You can enable per-file-format permissions to user roles.

 * Per https://www.drupal.org/node/2824163, if the default export path defined
   by this module is in conflict for your website, you can alter it using
   [1]hook_menu_alter in a custom module. See users_export.api.php for an
   example.

   - Exporting:

     Visit admin/people/export, adjust your settings and click Download File.


   - Searching (views integration)

     If the views module is enabled this module ships with a view for searching
     users found at admin/people/find

   - API:
     Refer to users_export.api.php for api functions and hooks.


TROUBLESHOOTING
---------------

Large user counts will try to consume a good deal of memory and may exceed the 
PHP maximum execution time. In the advanced settings on the export form, you may
attempt to increase both of these values, but their efficacy is dependent upon
the security settings of your server.
You may have to alter these values at the server level if you continue to have
export problems with large numbers of users.

To test this you should enable the test mode and you will get a file of 10
users. If this works, but when unchecked, the export fails, you are most likely
running into limits of either memory or time.

MAINTAINERS
-----------

Current maintainers:
  * Aaron Klump (aklump) - https://www.drupal.org/user/142422

This project has been sponsored by:
 * Loft Studios

Contact:
 * In the Loft Studios
  * Aaron Klump - Developer
  * PO Box 29294 Bellingham, WA 98228-1294
  * aim: theloft101
  * skype: intheloftstudios
  * d.o: aklump
  * [2]http://www.InTheLoftStudios.com

References

 1. https://api.drupal.org/api/drupal/modules%21system%21system.api.php/function/hook_menu_alter/7.x
 2. http://www.InTheLoftStudios.com/
