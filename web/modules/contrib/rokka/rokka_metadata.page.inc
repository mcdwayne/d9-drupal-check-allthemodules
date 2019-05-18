<?php

/**
 * @file
 * Contains rokka_metadata.page.inc.
 *
 * Page callback for Rokka Metadata entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Rokka Metadata templates.
 *
 * Default template: rokka_metadata.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_rokka_metadata(array &$variables) {
  // Fetch RokkaMetadata Entity Object.
  $rokka_metadata = $variables['elements']['#rokka_metadata'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
