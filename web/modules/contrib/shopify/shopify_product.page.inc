<?php

/**
 * @file
 * Contains shopify_product.page.inc..
 *
 * Page callback for Shopify product entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Shopify product templates.
 *
 * Default template: shopify_product.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_shopify_product(array &$variables) {
  // Fetch ShopifyProduct Entity Object.
  $shopify_product = $variables['elements']['#shopify_product'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
