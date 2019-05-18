<?php

/**
 * @file
 * Contains linky.page.inc.
 *
 * Page callback for Linky entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Linky templates.
 *
 * Default template: linky.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_linky(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
