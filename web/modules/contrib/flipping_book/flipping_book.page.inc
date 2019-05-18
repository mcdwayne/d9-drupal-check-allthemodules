<?php

/**
 * @file
 * Contains flipping_book.page.inc.
 *
 * Page callback for Flipping Book entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Flipping Book templates.
 *
 * Default template: flipping_book.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_flipping_book(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
