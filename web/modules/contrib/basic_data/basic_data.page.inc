<?php

/**
 * @file
 * Contains basic_data.page.inc.
 *
 * Page callback for Basic Data entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Basic Data templates.
 *
 * Default template: basic_data.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_basic_data(array &$variables) {
  // Fetch BasicData Entity Object.
  $basic_data = $variables['elements']['#basic_data'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
