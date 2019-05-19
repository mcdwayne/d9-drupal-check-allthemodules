<?php

/**
 * @file
 * Contains visualn_dataset.page.inc.
 *
 * Page callback for VisualN Data Set entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for VisualN Data Set templates.
 *
 * Default template: visualn_dataset.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_visualn_dataset(array &$variables) {
  // Fetch VisualNDataSet Entity Object.
  $visualn_dataset = $variables['elements']['#visualn_dataset'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
