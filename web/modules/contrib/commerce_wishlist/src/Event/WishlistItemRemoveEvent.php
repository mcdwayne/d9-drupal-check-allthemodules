<?php

namespace Drupal\commerce_wishlist\Event;

use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\commerce_wishlist\Entity\WishlistItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the wishlist item remove event.
 *
 * @see \Drupal\commerce_wishlist\Event\WishlistEvents
 */
class WishlistItemRemoveEvent extends Event {

  /**
   * The wishlist entity.
   *
   * @var \Drupal\commerce_wishlist\Entity\WishlistInterface
   */
  protected $wishlist;

  /**
   * The removed wishlist item.
   *
   * @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface
   */
  protected $wishlistItem;

  /**
   * Constructs a new WishlistWishlistItemRemoveEvent.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist
   *   The wishlist entity.
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item
   *   The removed wishlist item.
   */
  public function __construct(WishlistInterface $wishlist, WishlistItemInterface $wishlist_item) {
    $this->wishlist = $wishlist;
    $this->wishlistItem = $wishlist_item;
  }

  /**
   * Gets the wishlist entity.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistInterface
   *   The wishlist entity.
   */
  public function getWishlist() {
    return $this->wishlist;
  }

  /**
   * Gets the removed wishlist item.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistItemInterface
   *   The wishlist item entity.
   */
  public function getWishlistItem() {
    return $this->wishlistItem;
  }

}
