<?php

namespace Drupal\commerce_wishlist_api\Plugin\rest\resource;

use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\rest\ResourceResponse;

/**
 * Provides a wishlist collection resource for current session.
 *
 * @RestResource(
 *   id = "commerce_wishlist_canonical",
 *   label = @Translation("Wishlist canonical"),
 *   uri_paths = {
 *     "canonical" = "/wishlist/{commerce_wishlist}"
 *   }
 * )
 */
class WishlistCanonicalResource extends WishlistResourceBase {

  /**
   * GET a collection of the current user's wishlists.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $commerce_wishlist
   *   The wishlist.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The resource response.
   */
  public function get(WishlistInterface $commerce_wishlist) {
    $response = new ResourceResponse($commerce_wishlist);
    $response->addCacheableDependency($commerce_wishlist);
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    $route = parent::getBaseRoute($canonical_path, $method);

    $parameters = $route->getOption('parameters') ?: [];
    $parameters['commerce_wishlist']['type'] = 'entity:commerce_wishlist';
    $route->setOption('parameters', $parameters);

    return $route;
  }

}
