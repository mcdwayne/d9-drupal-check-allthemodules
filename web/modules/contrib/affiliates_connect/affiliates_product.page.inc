<?php

/**
 * @file
 * Contains affiliates_product.page.inc.
 *
 * Page callback for Affiliates Product entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Affiliates Product templates.
 *
 * Default template: affiliates_product.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_affiliates_product(array &$variables) {
  // Fetch AffiliatesProduct Entity Object.
  $affiliates_product = $variables['elements']['#affiliates_product'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
