<?php

/**
 * @file
 * Contains timeline.page.inc.
 *
 * Page callback for Timeline entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Timeline templates.
 *
 * Default template: timeline.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_timeline(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
