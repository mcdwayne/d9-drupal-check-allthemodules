<?php

/**
 * @file
 * Contains resources.page.inc.
 *
 * Page callback for Resources entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Resources templates.
 *
 * Default template: resources.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_resources(array &$variables) {
  // Fetch Resources Entity Object.
  $resources = $variables['elements']['#resources'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
