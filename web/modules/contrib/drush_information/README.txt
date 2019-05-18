INTRODUCTION
------------

 Provides the ability to view all available drush commands associated
 with the enabled modules for the site.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/drush_info

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/drush_info


INSTALLATION
------------

Install as usual, see
https://drupal.org/documentation/install/modules-themes/modules-8 for further
information.

HOW TO ACCESS
-------------

To view the available drush commands provided by enabled modules, go to
/admin/structure/drush-info.


TROUBLESHOOTING
---------------

If you find you're having trouble accessing Drush Information, make sure you
have the appropriate permissions. If that doesn't work, try clearing your
cache.

Below are three ways to clear your cache:
1. UI - Navigate to 'admin/config/development/performance' and click
   the 'Clear all caches' button.
2. Drush - from command line, enter 'drush cr all'.
3. Drupal console - from the command line, enter 'drupal cache:rebuild'.


MAINTAINERS
-----------

Current maintainers:

 * Brian McVeigh (https://drupal.org/user/3082379)
