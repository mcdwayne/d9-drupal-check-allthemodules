<?php

/**
 * @file
 * Contains homebox_layout.page.inc.
 *
 * Page callback for Homebox Layout entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Homebox Layout templates.
 *
 * Default template: homebox_layout.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_homebox_layout(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
