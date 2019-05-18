<?php

/**
 * @file
 * Contains entity_split.page.inc.
 *
 * Page callback for Entity split entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Entity split templates.
 *
 * Default template: entity_split.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_entity_split(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
