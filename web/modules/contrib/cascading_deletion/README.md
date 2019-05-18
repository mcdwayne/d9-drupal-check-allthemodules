CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Configuration
 * Requirements
 * Installation
 * Examples
 * Maintainers


INTRODUCTION
------------

  * This module manages cascading deletion on associated entities.


CONFIGURATION
------------

  Base Field Definition
  ---------------------

  * In entity static function "baseFieldDefinitions", add the cascading deletion setting in the as follow:
    ->setSetting('cascading_deletion', ['enabled' => TRUE])

  Field Config
  ------------   

  * In edit field configuration form of entity reference fields, it is shown a section called "Cascading Deletion".

  * It is just necessary check the checkbox and the module will be in charge of the rest.


REQUIREMENTS
------------

  * This module requires the following modules:
    - drupal:entity_reference


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


EXAMPLES
------------

  * Foo is father entity type of Bar (in relation 1 to N, for example).
    Deleting a Foo entity instance, all Bar entity instances with a reference to Foo (if enabled from field settings) will be deleted too.
    At this point, if there are other entity types related to Bar, they will be deleted.


MAINTAINERS
-----------

Current maintainers:
  * Gaetano Fabozzo (suerterapida) - https://www.drupal.org/user/3439517