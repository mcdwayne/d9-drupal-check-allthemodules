<?php

/**
 * @file
 * Contains visualn_drawing.page.inc.
 *
 * Page callback for VisualN Drawing entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for VisualN Drawing templates.
 *
 * Default template: visualn_drawing.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_visualn_drawing(array &$variables) {
  // Fetch VisualNDrawing Entity Object.
  $visualn_drawing = $variables['elements']['#visualn_drawing'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
