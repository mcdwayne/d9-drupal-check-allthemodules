<?php

/**
 * @file
 * Contains entity_log.page.inc.
 *
 * Page callback for Entity log entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Entity log templates.
 *
 * Default template: entity_log.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_entity_log(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
