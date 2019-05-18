<?php

/**
 * @file
 * Contains entity_modifier.page.inc.
 *
 * Page callback for Entity modifier entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Entity modifier templates.
 *
 * Default template: entity_modifier.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_entity_modifier(array &$variables) {
  // Fetch EntityModifier Entity Object.
  $entity_modifier = $variables['elements']['#entity_modifier'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
