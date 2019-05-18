<?php

/**
 * @file
 * Contains cml.page.inc.
 *
 * Page callback for Cml entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Cml entity templates.
 *
 * Default template: cml.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_cml(array &$variables) {
  // Fetch CmlEntity Entity Object.
  $cml = $variables['elements']['#cml'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
