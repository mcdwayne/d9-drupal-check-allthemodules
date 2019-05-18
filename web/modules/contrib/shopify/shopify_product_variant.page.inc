<?php

/**
 * @file
 * Contains shopify_product_variant.page.inc..
 *
 * Page callback for Shopify product variant entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Shopify product variant templates.
 *
 * Default template: shopify_product_variant.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_shopify_product_variant(array &$variables) {
  // Fetch ShopifyProductVariant Entity Object.
  $shopify_product_variant = $variables['elements']['#shopify_product_variant'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
