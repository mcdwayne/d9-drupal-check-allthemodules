INTRODUCTION
------------

The Whippy forms module adds a helper functionality for developers
and provide theme_hook_suggestions and calls preprocess functions
for Drupal forms, which allows you to threat forms like all other
entities in Drupal.

REQUIREMENTS
------------

No dependencies for this module.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------

The module has configuration form, use path admin/config/whippy_forms. When
enabled, the module will provide theme_hook_suggestions and calls for preprocess
functions like:
 - suggestions: $theme_name . '_' . $form_id
 - preprocess functions: $theme_name . '_preprocess_form__' . $form_id
for enabled themes on configuration page.

MAINTAINERS
-----------

Current maintainers:
 * Alexander Shumenko (shumer) - https://www.drupal.org/u/shumer
