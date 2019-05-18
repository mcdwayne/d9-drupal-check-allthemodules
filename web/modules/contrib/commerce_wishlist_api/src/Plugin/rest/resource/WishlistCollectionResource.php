<?php

namespace Drupal\commerce_wishlist_api\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;

/**
 * Provides a wishlist collection resource for current session.
 *
 * @RestResource(
 *   id = "commerce_wishlist_collection",
 *   label = @Translation("Wishlist collection"),
 *   uri_paths = {
 *     "canonical" = "/wishlist"
 *   }
 * )
 */
class WishlistCollectionResource extends WishlistResourceBase {

  /**
   * GET a collection of the current user's wishlists.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The resource response.
   */
  public function get() {
    $wishlists = $this->wishlistProvider->getWishlists();

    $response = new ResourceResponse(array_values($wishlists), 200);
    /** @var \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist */
    foreach ($wishlists as $wishlist) {
      $response->addCacheableDependency($wishlist);
    }
    $response->getCacheableMetadata()->addCacheContexts([
      'wishlist',
    ]);
    return $response;
  }

}
