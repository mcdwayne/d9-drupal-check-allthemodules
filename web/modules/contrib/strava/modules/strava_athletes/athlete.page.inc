<?php

/**
 * @file
 * Contains athlete.page.inc.
 *
 * Page callback for Athlete entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Athlete templates.
 *
 * Default template: athlete.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_athlete(array &$variables) {
  // Fetch Athlete Entity Object.
  $athlete = $variables['elements']['#athlete'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
