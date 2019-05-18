<?php

/**
 * @file
 * Contains state.page.inc.
 *
 * Page callback for State entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for State templates.
 *
 * Default template: state.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_state(array &$variables) {
  // Fetch State Entity Object.
  $state = $variables['elements']['#state'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
