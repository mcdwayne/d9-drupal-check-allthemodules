CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 
INTRODUCTION
------------
Light weight solution for redirection on node delete. Module provides
configuration form allowing user to redirect to specific path on the
site after deleting a node of content type.

REQUIREMENTS
------------
This module has no dependencies but the system module called 'Node'.

RECOMMENDED MODULES
-------------------
Delete all (https://www.drupal.org/project/delete_all):
When enabled, display of the project's README.md help will be rendered.

INSTALLATION
------------
 * Install as you would normally install a contributed Drupal module.
 See:https://drupal.org/documentation/install/modules-themes/modules-7
 for further information.
   _ drush en node_delete_redirect -y

CONFIGURATION
-------------
 * Go to /admin/people/permissions

   - Ensure that the right user has permission to administer
     node delete redirect settings.

 * Go to /admin/config/content/node-delete-settings

   - Enable redirect, allow for content type where the
    redirection is needed & provide the path. Test by deleting
    a node of that content type.

TROUBLESHOOTING
---------------
If the module configuration does not work as intended, please report at
drupal.org by creating an issue
see:https://www.drupal.org/issue-queue/how-to

FAQ
---
Q: How to check if the module is working as intended?

A: If redirection is set for a content type, you can test it by
inserting a test node of that content type & deleting the node.
