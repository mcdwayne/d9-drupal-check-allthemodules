<?php

/**
 * @file
 * Contains result.page.inc.
 *
 * Page callback for Result entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Result templates.
 *
 * Default template: result.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_result(array &$variables) {
  // Fetch Result Entity Object.
  $result = $variables['elements']['#result'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
