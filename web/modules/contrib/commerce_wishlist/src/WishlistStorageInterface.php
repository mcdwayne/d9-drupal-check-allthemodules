<?php

namespace Drupal\commerce_wishlist;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for wishlist storage.
 */
interface WishlistStorageInterface extends ContentEntityStorageInterface {

  /**
   * Loads the wishlist for the given code.
   *
   * @param string $code
   *   The code.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistInterface|null
   *   The wishlist, or NULL if none found.
   */
  public function loadByCode($code);

}
