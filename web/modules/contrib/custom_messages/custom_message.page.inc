<?php

/**
 * @file
 * Contains custom_message.page.inc.
 *
 * Page callback for Custom Message entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Custom Message templates.
 *
 * Default template: custom_message.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_custom_message(array &$variables) {
  // Fetch CustomMessage Entity Object.
  $custom_message = $variables['elements']['#custom_message'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
