<?php

/**
 * @file
 * Contains bills.page.inc.
 *
 * Page callback for Bills entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Bills templates.
 *
 * Default template: bills.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_bills(array &$variables) {
  // Fetch Bills Entity Object.
  $bills = $variables['elements']['#bills'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
