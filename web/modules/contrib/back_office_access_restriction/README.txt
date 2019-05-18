CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Back-office Access Restriction module is intended to be installed on production
sites.
It allows to restrict access to specific administration even for users with
corresponding permissions.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/back_office_access_restriction

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/back_office_access_restriction?categories=All


REQUIREMENTS
------------

No special requirements.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/node/895232 for further information.


CONFIGURATION
-------------

By default when enabling the module the Extend page will return a 403 response but
you can define any other specific route by providing the backoffice_access_restriction.routes
service parameter in the sites/default/services.yml file.
Example:

parameters:
  backoffice_access_restriction.routes:
    - system.modules_list
    - system.modules_uninstall
    - user.admin_permissions


MAINTAINERS
-----------

Current maintainers:
 * Romain Jarraud (romainj) - https://www.drupal.org/u/romainj

This project has been sponsored by:
 * Trained People
   Drupal shop focused on training and consulting.
