<?php

/**
 * @file
 * Contains advertising_product.page.inc.
 *
 * Page callback for Advertising Product entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\Intl\Intl;

/**
 * Prepares variables for Advertising Product templates.
 *
 * Default template: advertising_product.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_advertising_product(array &$variables) {
  // Fetch AdvertisingProduct Entity Object.
  $variables['advertising_product'] = $variables['elements']['#advertising_product'];
  $advertising_product = $variables['advertising_product'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  // Transform currency code into symbol
  $variables['formatted_currency'] = Intl::getCurrencyBundle()->getCurrencySymbol($advertising_product->product_currency->value);
}

/**
* Prepares variables for a custom entity type creation list templates.
*
* Default template: advertising_product-content-add-list.html.twig.
*
* @param array $variables
*   An associative array containing:
*   - content: An array of advertising_product-types.
*
* @see block_content_add_page()
*/
function template_preprocess_advertising_product_content_add_list(&$variables) {
  $variables['types'] = array();
  $query = \Drupal::request()->query->all();
  foreach ($variables['content'] as $type) {
    $variables['types'][$type->id()] = array(
      'link' => Link::fromTextAndUrl($type->label(), new Url('entity.advertising_product.add_form', array(
        'advertising_product_type' => $type->id()
      ), array('query' => $query))),
      'description' => array(
      '#markup' => $type->label(),
      ),
      'title' => $type->label(),
      'localized_options' => array(
      'query' => $query,
      ),
    );
  }
}
