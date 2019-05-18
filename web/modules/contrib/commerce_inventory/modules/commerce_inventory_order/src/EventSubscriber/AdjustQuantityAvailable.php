<?php

namespace Drupal\commerce_inventory_order\EventSubscriber;

use Drupal\commerce_inventory\Event\AdjustQuantityAvailableEvent;
use Drupal\commerce_inventory\EventSubscriber\AdjustQuantityAvailableSubscriberBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Listen to Inventory Item quantity-available check events.
 */
class AdjustQuantityAvailable extends AdjustQuantityAvailableSubscriberBase {

  /**
   * ID of the inventory available adjustment. Uses the service ID.
   *
   * @see \Drupal\commerce_order\Event\OrderItemEvent
   */
  const AVAILABLE_ADJUSTMENT_ID = 'commerce_inventory_order.quantity_availability_subscriber';

  /**
   * The Order Item entity storage.
   *
   * @var \Drupal\commerce_order\OrderItemStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new AdjustQuantityAvailable object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->storage = $entity_type_manager->getStorage('commerce_order_item');
  }

  /**
   * {@inheritdoc}
   */
  public function adjustQuantityAvailable(AdjustQuantityAvailableEvent $event) {
    $adjustment = 0;
    $inventory_id = $event->getInventoryItem()->id();

    // Lookup orders that have inventory quantity adjustments.
    $ids = $this->storage
      ->getQuery()
      ->condition('inventory_adjustment_state', 'available')
      ->condition('inventory_adjustment_holds.target_id', $inventory_id)
      ->condition('inventory_adjustment_holds.entity.status', TRUE)
      ->condition('inventory_adjustment_holds.entity.location_id.entity.status', TRUE)
      ->execute();

    // Exit early if no orders have adjustments.
    if (empty($ids)) {
      return;
    }

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface[] $items */
    $items = $this->storage->loadMultiple($ids);

    // Track order IDs for invalidation.
    $order_ids = [];
    $order_item_ids = [];

    // Get each adjustment and add to adjustment.
    foreach ($items as $item) {
      $values = $item->get('inventory_adjustment_holds')->getValue();

      // Add adjustment quantity.
      foreach ($values as $value) {
        if ($value['target_id'] == $inventory_id) {
          $adjustment -= $value['quantity'];
        }
      }

      // Track order and item IDs.
      if (!empty($adjustment)) {
        $order_ids[] = $item->getOrderId();
        $order_item_ids[] = $item->id();
      }
    }

    // Exit early if there is no adjustment to make.
    if (empty($adjustment)) {
      return;
    }

    // Add Order and Order Items to dependencies array.
    $dependencies = [
      'commerce_order' => $order_ids,
      'commerce_order_item' => $order_item_ids,
    ];

    // Add quantity adjustment to event.
    $event->addQuantityAdjustment($this::AVAILABLE_ADJUSTMENT_ID, $adjustment, $dependencies);
  }

}
