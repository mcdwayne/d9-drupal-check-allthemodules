<?php

namespace Drupal\commerce_wishlist;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\commerce_wishlist\Entity\WishlistItemInterface;
use Drupal\commerce_wishlist\Event\WishlistEvents;
use Drupal\commerce_wishlist\Event\WishlistEmptyEvent;
use Drupal\commerce_wishlist\Event\WishlistEntityAddEvent;
use Drupal\commerce_wishlist\Event\WishlistItemRemoveEvent;
use Drupal\commerce_wishlist\Event\WishlistItemUpdateEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Default implementation of the wishlist manager.
 *
 * Fires its own events, different from the wishlist entity events by being a
 * result of user interaction (add to wishlist form, wishlist view, etc).
 */
class WishlistManager implements WishlistManagerInterface {

  /**
   * The wishlist item storage.
   *
   * @var \Drupal\commerce_wishlist\WishlistItemStorageInterface
   */
  protected $wishlistItemStorage;

  /**
   * The wishlist item matcher.
   *
   * @var \Drupal\commerce_wishlist\WishlistItemMatcherInterface
   */
  protected $wishlistItemMatcher;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new WishlistManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_wishlist\WishlistItemMatcherInterface $wishlist_item_matcher
   *   The wishlist item matcher.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WishlistItemMatcherInterface $wishlist_item_matcher, EventDispatcherInterface $event_dispatcher) {
    $this->wishlistItemStorage = $entity_type_manager->getStorage('commerce_wishlist_item');
    $this->wishlistItemMatcher = $wishlist_item_matcher;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function emptyWishlist(WishlistInterface $wishlist, $save_wishlist = TRUE) {
    $wishlist_items = $wishlist->getItems();
    foreach ($wishlist_items as $wishlist_item) {
      $wishlist_item->delete();
    }
    $wishlist->setItems([]);

    $this->eventDispatcher->dispatch(WishlistEvents::WISHLIST_EMPTY, new WishlistEmptyEvent($wishlist, $wishlist_items));
    if ($save_wishlist) {
      $wishlist->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addEntity(WishlistInterface $wishlist, PurchasableEntityInterface $entity, $quantity = 1, $combine = TRUE, $save_wishlist = TRUE) {
    $wishlist_item = $this->createWishlistItem($entity, $quantity);
    return $this->addWishlistItem($wishlist, $wishlist_item, $combine, $save_wishlist);
  }

  /**
   * {@inheritdoc}
   */
  public function createWishlistItem(PurchasableEntityInterface $entity, $quantity = 1) {
    $wishlist_item = $this->wishlistItemStorage->createFromPurchasableEntity($entity, [
      'quantity' => $quantity,
    ]);

    return $wishlist_item;
  }

  /**
   * {@inheritdoc}
   */
  public function addWishlistItem(WishlistInterface $wishlist, WishlistItemInterface $wishlist_item, $combine = TRUE, $save_wishlist = TRUE) {
    $purchasable_entity = $wishlist_item->getPurchasableEntity();
    $quantity = $wishlist_item->getQuantity();
    $matching_wishlist_item = NULL;
    if ($combine) {
      $matching_wishlist_item = $this->wishlistItemMatcher->match($wishlist_item, $wishlist->getItems());
    }
    if ($matching_wishlist_item) {
      $new_quantity = bcadd($matching_wishlist_item->getQuantity(), $quantity, 0);
      $matching_wishlist_item->setQuantity($new_quantity);
      $matching_wishlist_item->save();
    }
    else {
      $wishlist_item->save();
      $wishlist->addItem($wishlist_item);
    }

    $event = new WishlistEntityAddEvent($wishlist, $purchasable_entity, $quantity, $wishlist_item);
    $this->eventDispatcher->dispatch(WishlistEvents::WISHLIST_ENTITY_ADD, $event);
    if ($save_wishlist) {
      $wishlist->save();
    }

    return $wishlist_item;
  }

  /**
   * {@inheritdoc}
   */
  public function updateWishlistItem(WishlistInterface $wishlist, WishlistItemInterface $wishlist_item, $save_wishlist = TRUE) {
    /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $original_wishlist_item */
    $original_wishlist_item = $this->wishlistItemStorage->loadUnchanged($wishlist_item->id());
    $wishlist_item->save();
    $event = new WishlistItemUpdateEvent($wishlist, $wishlist_item, $original_wishlist_item);
    $this->eventDispatcher->dispatch(WishlistEvents::WISHLIST_ITEM_UPDATE, $event);
    if ($save_wishlist) {
      $wishlist->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeWishlistItem(WishlistInterface $wishlist, WishlistItemInterface $wishlist_item, $save_wishlist = TRUE) {
    $wishlist_item->delete();
    $wishlist->removeItem($wishlist_item);
    $this->eventDispatcher->dispatch(WishlistEvents::WISHLIST_ITEM_REMOVE, new WishlistItemRemoveEvent($wishlist, $wishlist_item));
    if ($save_wishlist) {
      $wishlist->save();
    }
  }

}
