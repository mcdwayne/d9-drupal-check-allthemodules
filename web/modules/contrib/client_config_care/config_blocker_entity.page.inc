<?php

/**
 * @file
 * Contains config_blocker_entity.page.inc.
 *
 * Page callback for Config blocker entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Config blocker entity templates.
 *
 * Default template: config_blocker_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_config_blocker_entity(array &$variables) {
  // Fetch ConfigBlockerEntity Entity Object.
  $config_blocker_entity = $variables['elements']['#config_blocker_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
