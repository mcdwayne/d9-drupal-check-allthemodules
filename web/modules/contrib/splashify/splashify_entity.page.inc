<?php

/**
 * @file
 * Contains splashify_entity.page.inc.
 *
 * Page callback for Splashify entity entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Splashify entity templates.
 *
 * Default template: splashify_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_splashify_entity(array &$variables) {
  // Fetch SplashifyEntity Entity Object.
  $splashify_entity = $variables['elements']['#splashify_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
