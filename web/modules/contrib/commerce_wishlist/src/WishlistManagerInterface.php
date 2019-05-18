<?php

namespace Drupal\commerce_wishlist;

use Drupal\commerce_wishlist\Entity\WishlistItemInterface;
use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\commerce\PurchasableEntityInterface;

/**
 * Manages the wishlist and its wishlist items.
 */
interface WishlistManagerInterface {

  /**
   * Empties the given wishlist entity.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist
   *   The wishlist entity.
   * @param bool $save_wishlist
   *   Whether the wishlist should be saved after the operation.
   */
  public function emptyWishlist(WishlistInterface $wishlist, $save_wishlist = TRUE);

  /**
   * Adds the given purchasable entity to the given wishlist entity.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist
   *   The wishlist entity.
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param bool $combine
   *   Whether the wishlist item should be combined with an existing matching
   *   one.
   * @param bool $save_wishlist
   *   Whether the wishlist should be saved after the operation.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistItemInterface
   *   The saved wishlist item.
   */
  public function addEntity(WishlistInterface $wishlist, PurchasableEntityInterface $entity, $quantity = 1, $combine = TRUE, $save_wishlist = TRUE);

  /**
   * Creates a wishlist item for the given purchasable entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistItemInterface
   *   The created wishlist item. Unsaved.
   */
  public function createWishlistItem(PurchasableEntityInterface $entity, $quantity = 1);

  /**
   * Adds the given wishlist item to the given wishlist entity.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist
   *   The wishlist entity.
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item
   *   The wishlist item.
   * @param bool $combine
   *   Whether the wishlist item should be combined with an existing matching
   *   one.
   * @param bool $save_wishlist
   *   Whether the wishlist should be saved after the operation.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistItemInterface
   *   The saved wishlist item.
   */
  public function addWishlistItem(WishlistInterface $wishlist, WishlistItemInterface $wishlist_item, $combine = TRUE, $save_wishlist = TRUE);

  /**
   * Updates the given wishlist item.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist
   *   The wishlist entity.
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item
   *   The wishlist item.
   * @param bool $save_wishlist
   *   Whether the wishlist should be saved after the operation.
   */
  public function updateWishlistItem(WishlistInterface $wishlist, WishlistItemInterface $wishlist_item, $save_wishlist = TRUE);

  /**
   * Removes the given wishlist item from the wishlist entity.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist
   *   The wishlist entity.
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item
   *   The wishlist item.
   * @param bool $save_wishlist
   *   Whether the wishlist should be saved after the operation.
   */
  public function removeWishlistItem(WishlistInterface $wishlist, WishlistItemInterface $wishlist_item, $save_wishlist = TRUE);

}
