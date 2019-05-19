<?php

/**
 * @file
 * Contains club.page.inc.
 *
 * Page callback for Club entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Club templates.
 *
 * Default template: club.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_club(array &$variables) {
  // Fetch Club Entity Object.
  $club = $variables['elements']['#club'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
