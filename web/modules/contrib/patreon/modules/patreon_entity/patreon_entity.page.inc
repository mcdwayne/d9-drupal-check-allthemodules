<?php

/**
 * @file
 * Contains patreon_entity.page.inc.
 *
 * Page callback for Patreon entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Patreon entity templates.
 *
 * Default template: patreon_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_patreon_entity(array &$variables) {
  // Fetch PatreonEntity Entity Object.
  $patreon_entity = $variables['elements']['#patreon_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
