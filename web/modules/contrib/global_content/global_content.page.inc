<?php

/**
 * @file
 * Contains global_content.page.inc.
 *
 * Page callback for Global Content entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Global Content templates.
 *
 * Default template: global_content.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_global_content(array &$variables) {
  // Fetch GlobalContent Entity Object.
  $global_content = $variables['elements']['#global_content'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
