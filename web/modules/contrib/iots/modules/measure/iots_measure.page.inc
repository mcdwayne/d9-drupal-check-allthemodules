<?php

/**
 * @file
 * Contains iots_measure.page.inc.
 *
 * Page callback for Iots Measure entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Iots Measure templates.
 *
 * Default template: iots_measure.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_iots_measure(array &$variables) {
  // Fetch IotsMeasure Entity Object.
  $iots_measure = $variables['elements']['#iots_measure'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
