<?php

/**
 * @file
 * Contains sponsor.page.inc.
 *
 * Page callback for Sponsor entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Sponsor templates.
 *
 * Default template: sponsor.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_sponsor(array &$variables) {
  // Fetch Sponsor Entity Object.
  $sponsor = $variables['elements']['#sponsor'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
