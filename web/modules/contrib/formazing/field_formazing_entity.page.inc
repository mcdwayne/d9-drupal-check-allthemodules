<?php

/**
 * @file
 * Contains field_formazing_entity.page.inc.
 *
 * Page callback for Field formazing entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Field formazing entity templates.
 *
 * Default template: field_formazing_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_field_formazing_entity(array &$variables) {

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
