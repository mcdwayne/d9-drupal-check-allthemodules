<?php

namespace Drupal\commerce_inventory_store\EventSubscriber;

use Drupal\commerce_inventory_store\Event\StoreItemLookupEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Listen to Inventory Item quantity-available check events.
 */
class DefaultStoreItemLookupSubscriber extends StoreItemLookupSubscriberBase {

  /**
   * The Commerce Inventory Item storage.
   *
   * @var \Drupal\commerce_inventory\Entity\Storage\InventoryItemStorageInterface
   */
  protected $storage;

  /**
   * Constructs a StoreItemLookupSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->storage = $entity_type_manager->getStorage('commerce_inventory_item');
  }

  /**
   * {@inheritdoc}
   */
  public function addItems(StoreItemLookupEvent $event) {
    // Get store and location values from event.
    $field_locations = $event->getStore()->get('commerce_inventory_locations')->getValue();
    $location_ids = array_column($field_locations, 'target_id');
    $purchasable_entity_type_id = $event->getPurchasableEntity()->getEntityTypeId();
    $purchasable_entity_id = $event->getPurchasableEntity()->id();

    // Exit early if no locations have been added to the store.
    if (empty($location_ids)) {
      return;
    }

    // Build entity query and get Item IDs.
    $item_ids = $this->storage->getQuery()
      ->condition('location_id', $location_ids, 'IN')
      ->condition('location_id.entity.status', TRUE)
      ->condition('purchasable_entity__target_type', $purchasable_entity_type_id)
      ->condition('purchasable_entity__target_id', $purchasable_entity_id)
      ->condition('status', TRUE)
      ->execute();

    // Get related Location IDs.
    $item_location_ids = $this->storage->getLocationIds($item_ids);
    $location_item_ids = array_flip($item_location_ids);

    // Get inactive Location IDs to invalidate cache if reactivated.
    $inactive_item_ids = $this->storage->getQuery()
      ->condition('location_id', $location_ids, 'IN')
      ->condition('purchasable_entity__target_type', $purchasable_entity_type_id)
      ->condition('purchasable_entity__target_id', $purchasable_entity_id)
      ->condition(
        $this->storage->getQuery()->orConditionGroup()
          ->condition('location_id.entity.status', FALSE)
          ->condition('status', FALSE)
      )
      ->execute();
    $inactive_item_location_ids = $this->storage->getLocationIds($inactive_item_ids);
    $inactive_cache_tags = [];
    foreach ($inactive_item_location_ids as $item_id => $location_id) {
      // @todo should these be different tags to clear only on de/activate?
      // 'commerce_inventory_item:8:inactive'
      $inactive_cache_tags[] = 'commerce_inventory_item:' . $item_id;
      $inactive_cache_tags[] = 'commerce_inventory_location:' . $location_id;
    }
    $event->addCacheTags($inactive_cache_tags);

    // Add Item and Location IDs to event.
    foreach ($location_ids as $delta => $location_id) {
      if (array_key_exists($location_id, $location_item_ids)) {
        $event->addItem($location_item_ids[$location_id], $location_id, $delta - 10);
      }
    }
  }

}
