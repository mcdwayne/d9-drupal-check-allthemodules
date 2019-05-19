<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

/**
 * Preprocesses "webform_element_dropzonejs"
 */
function template_preprocess_webform_element_dropzonejs(array &$variables) {
  module_load_include('inc', 'webform', 'includes/webform.theme.template');
  template_preprocess_webform_element_managed_file($variables);
}
