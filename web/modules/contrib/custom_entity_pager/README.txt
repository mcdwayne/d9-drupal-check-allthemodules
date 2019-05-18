CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Installation
 * Configuration
 * Features
 * Examples

INTRODUCTION
-------------
The Custom Entity Pager module provides a custom Twig function that creates a pager for nodes. The main goal of this
module is performance, not complexity.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------
This module has no configuration pages.

FEATURES
--------
- This module provides a twig function called custom_entity_pager_insert()

- This function have 4 parameters, only the first parameter is required:
  * Parameter 1 (Required): (String) The machine name of content type.
  * Parameter 2 (Optional): (String) The machine name of the field of node to sort results. By default is the created
                                     time. Should be a field, not a property of node table.
  * Parameter 3 (Optional): (Boolean) Enable to show the titles of previous and next node. By default is TRUE
  * Parameter 4 (Optional): (String)  The text between the previous node and the next node. By default is null.

- So far, you will have to bring your own CSS

EXAMPLES
--------

Call this function in any twig template file:

custom_entity_pager_insert('article')
custom_entity_pager_insert('article', 'field_date')
custom_entity_pager_insert('article', 'field_date', TRUE)
custom_entity_pager_insert('article', 'field_date', FALSE, 'Pager')
custom_entity_pager_insert('article', 'field_date', TRUE, 'Pager')
