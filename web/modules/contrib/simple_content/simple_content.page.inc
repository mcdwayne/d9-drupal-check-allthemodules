<?php

/**
 * @file
 * Contains simple_content.page.inc.
 *
 * Page callback for Simple content entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Simple content templates.
 *
 * Default template: simple_content.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_simple_content(array &$variables) {
  // Fetch SimpleContent Entity Object.
  $simple_content = $variables['elements']['#simple_content'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
