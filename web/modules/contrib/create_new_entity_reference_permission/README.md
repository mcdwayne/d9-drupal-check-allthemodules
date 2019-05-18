INTRODUCTION
------------
 
This module adds a new permission "Create new autocomplete referenced entity"
which takes effect when your entity reference autocomplete widgets are switched
to "Autocomplete (with new entity permission)".

* For a full description of the module, visit the project page:
  https://www.drupal.org/project/create_new_entity_reference_permission

* To submit bug reports and feature suggestions, or to track changes:
  https://drupal.org/project/issues/create_new_entity_reference_permission


INSTALLATION
------------

* Install as you would normally install a contributed Drupal module. See:
  https://www.drupal.org/docs/8/extending-drupal-8/installing-modules for
  further information.

* Or install this module with composer using command below:
  composer require drupal/create_new_entity_reference_permission

* Or install this module with Drush command like below:
  drush dl create_new_entity_reference_permission
  drush en create_new_entity_reference_permission
  see:
  https://www.drupal.org/docs/8/extending-drupal-8/installing-modules-from-the-
  command-line
  for further information.


CONFIGURATION
------------

* Add a new Entity Reference field (or you can adjust an existing field).

* Set the "Reference Type" to "Create referenced entities if they don't already
  exist".

* On the "Manage form display" page for the field, choose the "Autocomplete
  (with new entity permission)" widget for the field.

* Grant selected user roles the "Create new autocomplete referenced entity"
  permission.


MAINTAINERS
-----------

Current maintainers:

* Gideon Cresswell (https://www.drupal.org/u/drupalgideon)

This project has been sponsored by:

* The Haunted Fish Tank
  Providing Drupal services since 2008 as well as operating our own e-commerce
  websites, built using Drupal Commerce.
