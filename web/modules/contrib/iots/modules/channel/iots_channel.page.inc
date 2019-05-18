<?php

/**
 * @file
 * Contains iots_channel.page.inc.
 *
 * Page callback for Iots Channel entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Iots Channel templates.
 *
 * Default template: iots_channel.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_iots_channel(array &$variables) {
  // Fetch IotsChannel Entity Object.
  $iots_channel = $variables['elements']['#iots_channel'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
