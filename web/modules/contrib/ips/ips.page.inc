<?php

/**
 * @file
 * Contains ips.page.inc.
 *
 * Page callback for Ips entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Ips templates.
 *
 * Default template: ips.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_ips(array &$variables) {
  // Fetch Ips Entity Object.
  $ips = $variables['elements']['#ips'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
