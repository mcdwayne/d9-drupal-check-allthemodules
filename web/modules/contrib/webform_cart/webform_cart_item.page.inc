<?php

/**
 * @file
 * Contains webform_cart_item.page.inc.
 *
 * Page callback for Webform cart item entity entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Component\Serialization\Json;

/**
 * Prepares variables for Webform cart item entity templates.
 *
 * Default template: webform-cart-item.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_webform_cart_item(array &$variables) {
  // Fetch WebformCartItem Entity Object.
  $webform_cart_item = $variables['elements']['#webform_cart_item'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  $variables['content']['data2']['raw'] = Json::decode($webform_cart_item->get('data2')->getString());
}
