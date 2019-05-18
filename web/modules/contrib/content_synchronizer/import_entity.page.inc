<?php

/**
 * @file
 * Contains import_entity.page.inc.
 *
 * Page callback for Import entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Import templates.
 *
 * Default template: import_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_import_entity(array &$variables) {
  // Fetch ImportEntity Entity Object.
  $import_entity = $variables['elements']['#import_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
