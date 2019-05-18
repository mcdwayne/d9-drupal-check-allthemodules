<?php

/**
 * @file
 * Contains badge.page.inc.
 *
 * Page callback for Badge Name entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Badge Name templates.
 *
 * Default template: badge.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_badge(array &$variables) {
  // Fetch Badge Entity Object.
  $badge = $variables['elements']['#badge'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
