<?php

namespace Drupal\shopify\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\shopify\Entity\ShopifyProduct;
use Drupal\shopify\Entity\ShopifyProductVariant;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ShopifyAddedToCart.
 *
 * Provides a route to display an added to cart message for the user.
 */
class ShopifyAddedToCart extends ControllerBase {

  /**
   * Displays a message to the user.
   */
  public function displayMessage() {
    $request = \Drupal::request();
    $variant_id = $request->get('variant_id');
    $quantity = $request->get('quantity');

    $variant = ShopifyProductVariant::loadByVariantId($variant_id);
    if (!$variant instanceof ShopifyProductVariant) {
      return new Response('Product not found.', Response::HTTP_NOT_FOUND);
    }
    $product = ShopifyProduct::loadByVariantId($variant_id);
    if (!$product instanceof ShopifyProduct) {
      return new Response('Product not found.', Response::HTTP_NOT_FOUND);
    }

    $title = $variant->label() == 'Default Title' ? '' : '- ' . $variant->label();
    drupal_set_message(t('@quantity x @parent @title (@price) added to @cart_link.', [
      '@parent' => $product->label(),
      '@title' => $title,
      '@price' => shopify_currency_format($variant->price->value),
      '@cart_link' => \Drupal::l(t('your cart'), Url::fromUri('https://' . shopify_shop_info('domain') . '/cart', ['attributes' => ['target' => '_blank']])),
      '@quantity' => $quantity,
    ]));
    return new Response('okay', Response::HTTP_OK);
  }

}
