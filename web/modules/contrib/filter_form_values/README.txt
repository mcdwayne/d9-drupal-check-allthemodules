Filter Form Values


INTRODUCTION
=============

The Filter Form Values module is a developer module that adds in pre-filtering of form values on forms.

This can be useful when you are doing some form level field access (as opposed to entity level field access and validation) and you want to filter out some fields so that their values don't affect the entity on save.


REQUIREMENTS
============

This module is a developer module and as such is specified as a requirement by other modules.

Modules that want to filter form values out must implement hook_filter_form_values_filter_functions($form, $form_state) and return an array of functions that should be called.

Functions that process form values are usually of the form:

`modulename_filter_form_value($form, $form_display, $form_state_values, $entity, $field_name, $field_definition)`


INSTALLATION
============

Install as you would normally install a contributed Drupal module. See: https://www.drupal.org/documentation/install/modules-themes/modules-8


MAINTAINERS
===========

Current Maintainers:
* Michael Welford - https://www.drupal.org/u/mikejw

This project has been sponsored by:
* The University of Adelaide - https://www.drupal.org/university-of-adelaide
  Visit: http://www.adelaide.edu.au
