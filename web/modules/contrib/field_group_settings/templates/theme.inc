<?php

/**
 * @file
 * Preprocessors for fieldgroup elements.
 */

/**
 * Prepares variables for horizontal tabs templates.
 *
 * Default template: horizontal-tabs.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the details element. Properties used: #children.
 *
 */
function template_preprocess_field_group_settings(&$variables) {
  $element = $variables['element'];
  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';
}