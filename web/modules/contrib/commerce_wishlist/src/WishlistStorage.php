<?php

namespace Drupal\commerce_wishlist;

use Drupal\commerce\CommerceContentEntityStorage;

/**
 * Defines the wishlist storage.
 */
class WishlistStorage extends CommerceContentEntityStorage implements WishlistStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadByCode($code) {
    $wishlists = $this->loadByProperties(['code' => $code]);
    $wishlist = reset($wishlists);

    return $wishlist ?: NULL;
  }

}
