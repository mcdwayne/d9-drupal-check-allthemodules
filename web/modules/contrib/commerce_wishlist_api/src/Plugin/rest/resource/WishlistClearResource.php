<?php

namespace Drupal\commerce_wishlist_api\Plugin\rest\resource;

use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\rest\ModifiedResourceResponse;

/**
 * Clear wishlist items and reset the wishlist to initial.
 *
 * @RestResource(
 *   id = "commerce_wishlist_clear",
 *   label = @Translation("Wishlist clear"),
 *   uri_paths = {
 *     "canonical" = "/wishlist/{commerce_wishlist}/items"
 *   }
 * )
 */
class WishlistClearResource extends WishlistResourceBase {

  /**
   * DELETE all wishlist item from a wishlist.
   *
   * The ResourceResponseSubscriber provided by rest.module gets weird when
   * going through the serialization process. The method is not cacheable and
   * it does not have a body format, causing it to be considered invalid.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $commerce_wishlist
   *   The wishlist.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response.
   *
   * @see \Drupal\rest\EventSubscriber\ResourceResponseSubscriber::getResponseFormat
   */
  public function delete(WishlistInterface $commerce_wishlist) {
    $this->wishlistManager->emptyWishlist($commerce_wishlist);
    return new ModifiedResourceResponse(NULL, 204);
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
