<?php

/**
 * @file
 * Contains real_estate_agency.page.inc.
 *
 * Page callback for Agency entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Agency templates.
 *
 * Default template: real_estate_agency.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_real_estate_agency(array &$variables) {
  // Fetch Agency Entity Object.
  $real_estate_agency = $variables['elements']['#real_estate_agency'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
