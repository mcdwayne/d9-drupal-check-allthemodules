INTRODUCTION
------------
The module is just a Views style plugin that renders a View as a form and its
results as options of the select box element, radio buttons or checkboxes.

Other modules may alter such form and add submission handlers using
 * hook_form_alter()
 * hook_form_vspfo_form_alter()
 * hook_form_vspfo_form_[view_name]_[display_id]_alter()
where "vspfo_form_[view_name]_[display_id]" is the FORM_ID and "vspfo_form" is
the BASE_FORM_ID.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/vspfo
 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/2421411


INSTALLATION
------------
Install as you would normally install a contributed Drupal module. See:
https://www.drupal.org/documentation/install/modules-themes/modules-8
for further information.


CONFIGURATION
-------------
While editing a View, set the Format (i.e. Style) to the 'Form options'. In the
style configuration form select one field that will represent the option value
to utilize. You can then set that field to exclude from output. All other
displayed fields will be part of the option that will be displayed in the form.
Configure other settings as needed.
