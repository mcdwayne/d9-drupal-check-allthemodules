INTRODUCTION
------------

The Stop Administrator Login module will stop users from being able to login as
user 1. Administrators will still be able to login as user 1 using drush. The
idea behind this is to protect a site from the accidental loss of user 1's
password. Also from a security perspective (not sharing passwords) and for
auditing configuration/content changes it's much better if user 1 is not used.

For a full description of the module, visit the project page:
  https://www.drupal.org/project/stop_admin
To submit bug reports and feature suggestions, or to track changes:
  https://drupal.org/project/issues/stop_admin
For the complete documentation of the module, visit the documentation page:
  https://www.drupal.org/docs/8/modules/stop-administrator-login


REQUIREMENTS
------------

None.


RECOMMENDED MODULES
-------------------

Alternatives:
  https://www.drupal.org/project/paranoia


INSTALLATION
------------

It is recommended to install this module using composer.


CONFIGURATION
-------------

It is possible to override the configuration check if you are for example in a
situation where you still want to be able to login as user 1 on your DEV/STAG
server. Please refer to the documentation page on drupal.org:
https://www.drupal.org/docs/8/modules/stop-administrator-login/custom-logic-for-disabling


TROUBLESHOOTING
---------------

If you encounter issues please create an issue in the issue queue at:
http://drupal.org/project/issues/stop_admin.
For the full module documentation please see:
  https://www.drupal.org/docs/8/modules/stop-administrator-login


MAINTAINERS
-----------

Current maintainers:
 * Bram Driesen (BramDriesen) - https://www.drupal.org/u/BramDriesen
