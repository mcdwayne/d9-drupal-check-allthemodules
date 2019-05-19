<?php

/**
 * @file
 * Contains simplemeta.page.inc.
 *
 * Page callback for SimpleMeta entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for SimpleMeta templates.
 *
 * Default template: simplemeta.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_simplemeta(array &$variables) {
  // Fetch SimplemetaEntity Entity Object.
  $simplemeta = $variables['elements']['#simplemeta'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
