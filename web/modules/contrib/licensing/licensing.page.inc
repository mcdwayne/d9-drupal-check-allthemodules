<?php

/**
 * @file
 * Contains license.page.inc.
 *
 * Page callback for License entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for License templates.
 *
 * Default template: license.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_license(array &$variables) {
  // Fetch License Object.
  $license = $variables['elements']['#license'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
