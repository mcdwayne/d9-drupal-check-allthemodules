<?php

/**
 * @file
 * Preprocesses for custom theme hooks.
 */

/**
 * Implements template_preprocess_HOOK().
 */
function template_preprocess_fasp_stylesheets(&$variables) {
  $input_classes_array = $variables['selector'];
  // Add dots for each class.
  foreach ($input_classes_array as &$class) {
    // Protect from empty lines.
    if (strlen($class) > 0) {
      $class = '.' . $class;
    }
  }
  $variables['selector'] = implode(',', $input_classes_array);
}
