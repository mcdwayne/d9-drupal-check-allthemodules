<?php

namespace Drupal\commerce_wishlist;

use Drupal\commerce_wishlist\Event\WishlistAssignEvent;
use Drupal\commerce_wishlist\Event\WishlistEvents;
use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Default wishlist assignment implementation.
 */
class WishlistAssignment implements WishlistAssignmentInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new WishlistAssignment object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function assign(WishlistInterface $wishlist, UserInterface $account) {
    if (!empty($wishlist->getOwnerId())) {
      // Skip wishlists which already have an owner.
      return;
    }

    $wishlist->setOwner($account);
    // Update the referenced shipping profile.
    $shipping_profile = $wishlist->getShippingProfile();
    if ($shipping_profile && empty($shipping_profile->getOwnerId())) {
      $shipping_profile->setOwner($account);
      $shipping_profile->save();
    }
    // Notify other modules.
    $event = new WishlistAssignEvent($wishlist, $account);
    $this->eventDispatcher->dispatch(WishlistEvents::WISHLIST_ASSIGN, $event);

    $wishlist->save();
  }

  /**
   * {@inheritdoc}
   */
  public function assignMultiple(array $wishlists, UserInterface $account) {
    foreach ($wishlists as $wishlist) {
      $this->assign($wishlist, $account);
    }
  }

}
