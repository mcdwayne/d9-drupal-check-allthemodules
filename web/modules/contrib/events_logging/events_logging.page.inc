<?php

/**
 * @file
 * Contains events_logging.page.inc.
 *
 * Page callback for Event log entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Event log templates.
 *
 * Default template: events_logging.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_events_logging(array &$variables) {
  // Fetch EventLog Entity Object.
  $events_logging = $variables['elements']['#events_logging'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
