<?php

/**
 * @file
 * Contains badge_awarded.page.inc.
 *
 * Page callback for Badge awarded entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Badge awarded templates.
 *
 * Default template: badge_awarded.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_badge_awarded(array &$variables) {
  // Fetch BadgeAwarded Entity Object.
  $badge_awarded = $variables['elements']['#badge_awarded'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
