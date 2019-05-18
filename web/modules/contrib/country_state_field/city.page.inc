<?php

/**
 * @file
 * Contains city.page.inc.
 *
 * Page callback for City entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for City templates.
 *
 * Default template: city.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_city(array &$variables) {
  // Fetch City Entity Object.
  $city = $variables['elements']['#city'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
