<?php

namespace Drupal\shopify\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\shopify\Entity\ShopifyProduct;
use Drupal\shopify\Entity\ShopifyProductVariant;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ShopifyRedirect.
 *
 * Handles redirecting the user.
 */
class ShopifyRedirect extends ControllerBase {

  /**
   * Redirects the incoming user to the proper specific variant or product page.
   */
  public function handleRedirect() {
    $request = \Drupal::request();

    if ($request->get('variant_id')) {
      // We are redirecting to a specific variant page.
      $variant = ShopifyProductVariant::loadByVariantId($request->get('variant_id'));
      if ($variant instanceof ShopifyProductVariant) {
        return new RedirectResponse($variant->url());
      }
      drupal_set_message(t("We're sorry, but that product is unavailable at this time."), 'warning');
    }

    if ($request->get('product_id')) {
      // We are redirecting to a product page (no variant selected).
      $product = ShopifyProduct::loadByProductId($request->get('product_id'));
      if ($product instanceof ShopifyProduct) {
        return new RedirectResponse($product->url());
      }
      drupal_set_message(t("We're sorry, but that product is unavailable at this time."), 'warning');
    }

    if ($request->get('collection_id')) {
      // We are redirecting to a collection page.
      $collection = shopify_collection_load($request->get('collection_id'));
      if ($collection instanceof Term) {
        return new RedirectResponse($collection->url());
      }
      drupal_set_message(t("We're sorry, but that collection is unavailable at this time."), 'warning');
    }

    return new RedirectResponse('/' . shopify_store_url());
  }

  /**
   * Redirects the user to the admin page to add a new product.
   */
  public function addShopifyProduct() {
    return new TrustedRedirectResponse('https://' . shopify_shop_info('domain') . '/admin/products/new');
  }

}
