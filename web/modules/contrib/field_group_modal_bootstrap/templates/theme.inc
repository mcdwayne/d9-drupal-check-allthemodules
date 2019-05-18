<?php

/**
 * @file
 * Preprocessors for fieldgroup modal elements.
 */

/**
 * Prepares variables for fieldgroup html modal Bootstrap templates.
 *
 * Default template: field-group-modal-bootstrap.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the html element.
 */
function template_preprocess_field_group_modal_bootstrap(array &$variables) {

  $element = $variables['element'];

  if (!empty($element['#title'])) {
    $variables['title'] = $element['#title'];
  }

  $variables['button'] = $element['#button'];
  $variables['attributes'] = $element['#attributes'];
  $variables['id'] = $element['#id'];
  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';

}
