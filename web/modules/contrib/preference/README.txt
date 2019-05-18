Multi Select Preference

INTRODUCTION
------------

Multi Select Preference module will provide a widget for select list fields. When enabled, this widget will convert all multi select options (allowed values) into drag-able rows so that user can reorder the choices. This will help user to save more than one choice with preference.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/preference

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/preference

REQUIREMENTS
------------
No modules are required as such.

RECOMMENDED MODULES
-------------------

This module requires the following modules:

 * Views (https://drupal.org/project/views)


INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. Visit:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

 * You may want to disable Toolbar module, since its output clashes with
   Administration Menu.


HOW IT WORKS
-------------

The module has no menu or modifiable settings. There is no configuration. When
enabled, the module will provide create a FieldWidget as "Reorder" in admin/structure/types/manage/CONTENT-TYPE/form-display. 
Once enabled "Reorder" widget for any select list with multiple allowed values, all allowed values will be seen as reorderable format
in form. User can reorder to set preference of field values and save. 





