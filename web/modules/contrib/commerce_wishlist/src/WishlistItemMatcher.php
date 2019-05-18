<?php

namespace Drupal\commerce_wishlist;

use Drupal\commerce_wishlist\Event\WishlistEvents;
use Drupal\commerce_wishlist\Event\WishlistItemComparisonFieldsEvent;
use Drupal\commerce_wishlist\Entity\WishlistItemInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Default implementation of the wishlist item matcher.
 */
class WishlistItemMatcher implements WishlistItemMatcherInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new WishlistItemMatcher object.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function match(WishlistItemInterface $wishlist_item, array $wishlist_items) {
    $wishlist_items = $this->matchAll($wishlist_item, $wishlist_items);
    return count($wishlist_items) ? $wishlist_items[0] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function matchAll(WishlistItemInterface $wishlist_item, array $wishlist_items) {
    $purchasable_entity = $wishlist_item->getPurchasableEntity();
    if (empty($purchasable_entity)) {
      // Don't support combining wishlist items without a purchasable entity.
      return [];
    }

    $comparison_fields = ['type', 'purchasable_entity'];
    $event = new WishlistItemComparisonFieldsEvent($comparison_fields, $wishlist_item);
    $this->eventDispatcher->dispatch(WishlistEvents::WISHLIST_ITEM_COMPARISON_FIELDS, $event);
    $comparison_fields = $event->getComparisonFields();

    $matched_wishlist_items = [];
    /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $existing_wishlist_item */
    foreach ($wishlist_items as $existing_wishlist_item) {
      foreach ($comparison_fields as $comparison_field) {
        if (!$existing_wishlist_item->hasField($comparison_field) || !$wishlist_item->hasField($comparison_field)) {
          // The field is missing on one of the wishlist items.
          continue 2;
        }
        if ($existing_wishlist_item->get($comparison_field)->getValue() !== $wishlist_item->get($comparison_field)->getValue()) {
          // Wishlist item doesn't match.
          continue 2;
        }
      }
      $matched_wishlist_items[] = $existing_wishlist_item;
    }

    return $matched_wishlist_items;
  }

}
