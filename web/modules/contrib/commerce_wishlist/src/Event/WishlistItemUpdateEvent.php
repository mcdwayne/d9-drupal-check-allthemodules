<?php

namespace Drupal\commerce_wishlist\Event;

use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\commerce_wishlist\Entity\WishlistItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the wishlist item update event.
 *
 * @see \Drupal\commerce_wishlist\Event\WishlistEvents
 */
class WishlistItemUpdateEvent extends Event {

  /**
   * The wishlist entity.
   *
   * @var \Drupal\commerce_wishlist\Entity\WishlistInterface
   */
  protected $wishlist;

  /**
   * The updated wishlist item.
   *
   * @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface
   */
  protected $wishlistItem;

  /**
   * The original wishlist item.
   *
   * @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface
   */
  protected $originalWishlistItem;

  /**
   * Constructs a new WishlistItemUpdateEvent.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist
   *   The wishlist entity.
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item
   *   The updated wishlist item.
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $original_wishlist_item
   *   The original wishlist item.
   */
  public function __construct(WishlistInterface $wishlist, WishlistItemInterface $wishlist_item, WishlistItemInterface $original_wishlist_item) {
    $this->wishlist = $wishlist;
    $this->wishlistItem = $wishlist_item;
    $this->originalWishlistItem = $original_wishlist_item;
  }

  /**
   * Gets the wishlist.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistInterface
   *   The wishlist entity.
   */
  public function getWishlist() {
    return $this->wishlist;
  }

  /**
   * Gets the updated wishlist item.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistItemInterface
   *   The updated wishlist item.
   */
  public function getWishlistItem() {
    return $this->wishlistItem;
  }

  /**
   * Gets the original wishlist item.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistItemInterface
   *   The original wishlist item.
   */
  public function getOriginalWishlistItem() {
    return $this->originalWishlistItem;
  }

}
