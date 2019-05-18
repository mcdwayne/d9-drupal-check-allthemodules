<?php

namespace Drupal\commerce_wishlist_api\Plugin\rest\resource;

use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\commerce_wishlist\Entity\WishlistItemInterface;
use Drupal\rest\ModifiedResourceResponse;

/**
 * Provides a wishlist collection resource for current session.
 *
 * @RestResource(
 *   id = "commerce_wishlist_remove_item",
 *   label = @Translation("Wishlist remove item"),
 *   uri_paths = {
 *     "canonical" = "/wishlist/{commerce_wishlist}/items/{commerce_wishlist_item}"
 *   }
 * )
 */
class WishlistRemoveItemResource extends WishlistResourceBase {

  /**
   * DELETE an wishlist item from a wishlist.
   *
   * The ResourceResponseSubscriber provided by rest.module gets weird when
   * going through the serialization process. The method is not cacheable and
   * it does not have a body format, causing it to be considered invalid.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $commerce_wishlist
   *   The wishlist.
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $commerce_wishlist_item
   *   The wishlist item.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response.
   *
   * @see \Drupal\rest\EventSubscriber\ResourceResponseSubscriber::getResponseFormat
   */
  public function delete(WishlistInterface $commerce_wishlist, WishlistItemInterface $commerce_wishlist_item) {
    $this->wishlistManager->removeWishlistItem($commerce_wishlist, $commerce_wishlist_item);
    return new ModifiedResourceResponse(NULL, 204);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    $route = parent::getBaseRoute($canonical_path, $method);

    $parameters = $route->getOption('parameters') ?: [];
    $parameters['commerce_wishlist']['type'] = 'entity:commerce_wishlist';
    $parameters['commerce_wishlist_item']['type'] = 'entity:commerce_wishlist_item';
    $route->setOption('parameters', $parameters);

    return $route;
  }

}
