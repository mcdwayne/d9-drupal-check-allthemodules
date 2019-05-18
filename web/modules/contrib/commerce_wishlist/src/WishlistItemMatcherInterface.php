<?php

namespace Drupal\commerce_wishlist;

use Drupal\commerce_wishlist\Entity\WishlistItemInterface;

/**
 * Finds matching wishlist items.
 *
 * Used for combining wishlist items in the add to wishlist process.
 */
interface WishlistItemMatcherInterface {

  /**
   * Finds the best matching wishlist item for the given wishlist item.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item
   *   The wishlist item.
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface[] $wishlist_items
   *   The wishlist items to match against.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistItemInterface|null
   *   A matching wishlist item, or NULL if none was found.
   */
  public function match(WishlistItemInterface $wishlist_item, array $wishlist_items);

  /**
   * Finds all matching wishlist items for the given wishlist item.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item
   *   The wishlist item.
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface[] $wishlist_items
   *   The wishlist items to match against.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistItemInterface[]
   *   The matching wishlist items.
   */
  public function matchAll(WishlistItemInterface $wishlist_item, array $wishlist_items);

}
