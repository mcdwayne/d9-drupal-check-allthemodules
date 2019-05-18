<?php

/**
 * @file
 * Holds preprocess functions for the jQuery colorpicker module.
 */

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Implements hook_preprocess_jquery_colorpicker().
 */
function template_preprocess_jquery_colorpicker(&$vars) {
  $element = $vars['element'];

  $vars['id'] = $element['#id'];
  $vars['name'] = isset($element['#name']) ? $element['#name'] : $element['#id'];
  $vars['value'] = isset($element['#value']) ? $element['#value'] : '';

  $colorapi_service = \Drupal::service('colorapi.service');

  $default_color = '';
  if (isset($element['#value'])) {
    if ($colorapi_service->isValidHexadecimalColorString($element['#value'])) {
      $default_color = $element['#value'];
    }
  }
  elseif (isset($element['#default_value']) && $colorapi_service->isValidHexadecimalColorString($element['#value'])) {
    $default_color = $element['#default_value'];
  }

  $vars['default_color'] = $default_color;

  $vars['additional'] = '';
  $vars['show_remove'] = FALSE;
  $vars['show_clear'] = FALSE;

  if (isset($element['#cardinality'])) {
    if ($element['#cardinality'] == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $vars['show_remove'] = TRUE;
    }
    else {
      $vars['show_clear'] = TRUE;
    }
  }
}
