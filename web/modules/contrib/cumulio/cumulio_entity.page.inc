<?php

/**
 * @file
 * Contains cumulio_entity.page.inc.
 *
 * Page callback for Cumulio entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Cumulio entity templates.
 *
 * Default template: cumulio_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_cumulio_entity(array &$variables) {
  // Fetch CumulioEntity Entity Object.
  $cumulio_entity = $variables['elements']['#cumulio_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
