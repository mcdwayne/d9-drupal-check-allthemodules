<?php

/**
 * @file
 * Contains visualn_iframe.page.inc.
 *
 * Page callback for VisualN IFrame entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for VisualN IFrame templates.
 *
 * Default template: visualn_iframe.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_visualn_iframe(array &$variables) {
  // Fetch VisualNIFrame Entity Object.
  $visualn_iframe = $variables['elements']['#visualn_iframe'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
