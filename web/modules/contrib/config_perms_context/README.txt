CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Recommended Modules
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

Intergration Custom Permissions module with Context. This module allow dynamic
define custom permisions for Drupal routes.

 * For a full description of the module visit:
  https://www.drupal.org/project/config_perms_context

 * To submit bug reports and feature suggestions, or to track changes visit:
  https://www.drupal.org/project/issues/config_perms_context

Note: Custom Permissions not working with routes defined by Views.


REQUIREMENTS
------------

This module requires modules:
 * Context: https://www.drupal.org/project/context
 * Custom Permissions: https://www.drupal.org/project/config_perms (>=8.x-2.x)


RECOMMENDED MODULES
-------------------


INSTALLATION
------------

Install the optimizely module as you would normally install a contributed Drupal
module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
--------------

    1. Navigate to Administration > Extend and enable the Custom Permissions
       Context module.
    2. Navigate to Administration >  Structure > Context.
    3. Add new context and "Add condition".
    4. "Add reaction" with Custom Permissions.
    5. Select Custom permissions in list for allow and forbiden access.


MAINTAINERS
-----------

The project was created by:

 * Thao Huyn Khac (zipme_hkt) - https://www.drupal.org/u/zipme_hkt
