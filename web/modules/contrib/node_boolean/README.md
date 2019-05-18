Node Boolean Condition
======================


INTRODUCTION
------------

This module is a simple plugin to enable boolean fields on a node to be used as
a condition to determine whether a block should be visible, this can be used
against a single or multiple fields either on an 'and' or 'or' basis.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/node_boolean

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/node_boolean


REQUIREMENTS
------------

This module requires the following modules:

 * Node (from Core)


INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
for further information.


CONFIGURATION
-------------

To use this Condition you would need a node with a Boolean (checkbox) field
attached.

### Adding as a Block Condition

 *  Go to Block Layout (/admin/structure/block/)
 *  Select the block you wish to make optionally visible (click Configure)
 *  Scroll down to Visibility and open the Node Boolean tab
 *  Now select the field(s) you wish to be evaluated to determine visibility

### Additional options

#### Negate the condition

You can optionally select to have the module negate the selected fields value
(i.e. only show if this checkbox is not checked).

#### Evaluate all fields rather than any

You can optionally select to 'Evaluate all fields rather than any', so if you
are using multiple fields all fields must be set to true (checked) if you have
selected this option, otherwise the block will show if any of the field are
set to true (checked).
