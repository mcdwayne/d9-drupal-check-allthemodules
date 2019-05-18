<?php

/**
 * @file
 * Contains iots_device.page.inc.
 *
 * Page callback for Device entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Device templates.
 *
 * Default template: iots_device.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_iots_device(array &$variables) {
  // Fetch IotsDevice Entity Object.
  $iots_device = $variables['elements']['#iots_device'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
