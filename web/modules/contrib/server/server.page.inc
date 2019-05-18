<?php

/**
 * @file
 * Contains server.page.inc.
 *
 * Page callback for Server entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Server templates.
 *
 * Default template: server.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_server(array &$variables) {
  // Fetch Server Entity Object.
  $server = $variables['elements']['#server'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
