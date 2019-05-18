<?php

/**
 * @file
 * Contains permanent_entities.page.inc.
 *
 * Page callback for Permanent Entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Permanent Entity templates.
 *
 * Default template: permanent_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_permanent_entity(array &$variables) {
  // Fetch PermanentEntity Entity Object.
  $permanent_entity = $variables['elements']['#permanent_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
