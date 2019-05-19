<?php

/**
 * @file
 * Theme for views_rows_wrapper views.
 */

use Drupal\Core\Template\Attribute;
use Drupal\views_rows_wrapper\ViewsRowsWrapperTypes;

/**
 * Implements template_preprocess_hook().
 */
function template_preprocess_views_rows_wrapper(&$variables) {
  // View options set by user.
  $view = $variables['view'];
  $rows = $variables['rows'];
  $style = $view->style_plugin;
  $options = $style->options;
  $element_types = ViewsRowsWrapperTypes::elementTypes();
  $attribute_types = ViewsRowsWrapperTypes::attributeTypes();
  $variables['use_wrapper'] = !empty($options['use_wrapper']);
  $element_type = isset($options['element_type']) ? $element_types[$options['element_type']] : $element_types[0];
  $variables['element_type'] = strtolower($element_type);
  $attribute_type = isset($options['attribute_type']) ? $attribute_types[$options['attribute_type']] : $attribute_types[0];
  $variables['attribute_type'] = strtolower($attribute_type);
  $attribute_name = isset($options['attribute_name']) ? $options['attribute_name'] : '';
  $variables['attribute_name'] = preg_replace('/[^a-zA-Z0-9-_\s]/', '', $attribute_name);
  $variables['rows_number'] = isset($options['rows_number']) ? $options['rows_number'] : 2;
  $variables['wrap_method'] = isset($options['wrap_method']) ? $options['wrap_method'] : 1;
  $default_rows = isset($options['default_rows']) ? $options['default_rows'] : FALSE;
  $strip_rows = isset($options['strip_rows']) ? $options['strip_rows'] : FALSE;
  $count = 0;
  $max = count($rows);
  foreach ($rows as $id => $row) {
    $classes = [];
    $count++;
    $variables['rows'][$id] = [];
    $variables['rows'][$id]['content'] = $row;
    if ($default_rows) {
      $classes[] = 'views-row';
    }
    if ($strip_rows) {
      $classes[] = 'views-row-' . ($count % 2 ? 'odd' : 'even');
      if ($count == 1) {
        $classes[] = 'views-row-first';
      }
      if ($count == $max) {
        $classes[] = 'views-row-last';
      }
    }
    if (($default_rows) || ($strip_rows)) {
      $variables['rows'][$id]['attributes'] = new Attribute();
      $variables['rows'][$id]['attributes']->addClass($classes);
    }
  }
}
