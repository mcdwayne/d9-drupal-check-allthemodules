<?php

/**
 * @file
 * Contains country.page.inc.
 *
 * Page callback for Country entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Country templates.
 *
 * Default template: country.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_country(array &$variables) {
  // Fetch Country Entity Object.
  $country = $variables['elements']['#country'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
