<?php

/**
 * @file
 * Contains result_formazing_entity.page.inc.
 *
 * Page callback for Result formazing entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Result formazing entity templates.
 *
 * Default template: result_formazing_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_result_formazing_entity(array &$variables) {
  // Fetch ResultFormazingEntity Entity Object.
  $result_formazing_entity = $variables['elements']['#result_formazing_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
