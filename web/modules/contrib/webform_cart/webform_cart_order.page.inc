<?php

/**
 * @file
 * Contains webform_cart_order.page.inc.
 *
 * Page callback for Webform cart order entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Webform cart order entity templates.
 *
 * Default template: webform-cart-order.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_webform_cart_order(array &$variables) {
  // Fetch WebformCartOrder Entity Object.
  $webform_cart_order = $variables['elements']['#webform_cart_order'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
