Checklist Entity Reference
==========================


INTRODUCTION
------------

This module adds a new field type, widget and formatter to add a checklist
based on any entity type (e.g. taxonomy term, node, etc). On top of storing
the target id, it also stores the timestamp checked and user to display in
checklist.

It also adds a progress bar widget so on an entity form can show all
checklist progress at a glance.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/checklist_entity_reference

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/checklist_entity_reference


INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
for further information.


CONFIGURATION
-------------

Add an Entity Reference (Checklist) field like you would any other field
type and then select the entity type and bundle you wish to reference.

In the form display screen add the field to where you wish it to show
on the form.

Additionally you could drag in the progress bar widget which will show
the cumulative progress for all checklist fields on a page.
