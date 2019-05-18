<?php

namespace Drupal\commerce_wishlist\Event;

use Drupal\commerce_wishlist\Entity\WishlistItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the wishlist item comparison fields event.
 *
 * @see \Drupal\commerce_wishlist\Event\WishlistEvents
 */
class WishlistItemComparisonFieldsEvent extends Event {

  /**
   * The comparison fields.
   *
   * @var string[]
   */
  protected $comparisonFields;

  /**
   * The wishlist item being added to the wishlist.
   *
   * @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface
   */
  protected $wishlistItem;

  /**
   * Constructs a new WishlistItemComparisonFieldsEvent.
   *
   * @param string[] $comparison_fields
   *   The comparison fields.
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item
   *   The wishlist item being added to the wishlist.
   */
  public function __construct(array $comparison_fields, WishlistItemInterface $wishlist_item) {
    $this->comparisonFields = $comparison_fields;
    $this->wishlistItem = $wishlist_item;
  }

  /**
   * Gets the comparison fields.
   *
   * @return string[]
   *   The comparison fields.
   */
  public function getComparisonFields() {
    return $this->comparisonFields;
  }

  /**
   * Sets the comparison fields.
   *
   * @param string[] $comparison_fields
   *   The comparison fields.
   */
  public function setComparisonFields(array $comparison_fields) {
    $this->comparisonFields = $comparison_fields;
  }

  /**
   * The wishlist item being added to the wishlist.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistItemInterface
   *   The wishlist item being added to the wishlist.
   */
  public function getWishlistItem() {
    return $this->wishlistItem;
  }

}
