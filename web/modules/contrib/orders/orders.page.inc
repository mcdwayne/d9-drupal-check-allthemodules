<?php

/**
 * @file
 * Contains orders.page.inc.
 *
 * Page callback for Orders entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Orders templates.
 *
 * Default template: orders.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_orders(array &$variables) {
  // Fetch Orders Entity Object.
  $orders = $variables['elements']['#orders'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
