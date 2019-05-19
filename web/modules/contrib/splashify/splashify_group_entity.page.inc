<?php

/**
 * @file
 * Contains splashify_group_entity.page.inc.
 *
 * Page callback for Splashify group entity entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Splashify group entity templates.
 *
 * Default template: splashify_group_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_splashify_group_entity(array &$variables) {
  // Fetch SplashifyGroupEntity Entity Object.
  $splashify_group_entity = $variables['elements']['#splashify_group_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
