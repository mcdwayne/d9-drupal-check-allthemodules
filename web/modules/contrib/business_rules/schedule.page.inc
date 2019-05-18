<?php

/**
 * @file
 * Contains schedule.page.inc.
 *
 * Page callback for Schedule entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Schedule templates.
 *
 * Default template: schedule.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_schedule(array &$variables) {
  // Fetch Schedule Entity Object.
  $schedule = $variables['elements']['#schedule'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
