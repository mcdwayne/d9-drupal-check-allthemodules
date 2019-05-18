<?php

namespace Drupal\commerce_inventory_store\InventoryAllocator;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_inventory\InventoryAllocator\InventoryAllocation;
use Drupal\commerce_inventory\InventoryAllocator\InventoryAllocatorBase;
use Drupal\commerce_inventory\QuantityManagerInterface;
use Drupal\commerce_inventory_store\InventoryStoreManager;
use Drupal\commerce_store\Entity\Store;

/**
 * Returns the store inventory allocator.
 */
class StoreInventoryAllocator extends InventoryAllocatorBase {

  /**
   * The inventory Commerce Store manager.
   *
   * @var \Drupal\commerce_inventory_store\InventoryStoreManager
   */
  protected $inventoryStoreManager;

  /**
   * Constructs a new StoreInventoryPlacementResolver object.
   *
   * @param \Drupal\commerce_inventory\QuantityManagerInterface $quantity_available
   *   The quantity available manager.
   * @param \Drupal\commerce_inventory\QuantityManagerInterface $quantity_minimum
   *   The quantity minimum manager.
   * @param \Drupal\commerce_inventory_store\InventoryStoreManager $inventory_store_manager
   *   The inventory Commerce Store manager.
   */
  public function __construct(QuantityManagerInterface $quantity_available, QuantityManagerInterface $quantity_minimum, InventoryStoreManager $inventory_store_manager) {
    parent::__construct($quantity_available, $quantity_minimum);
    $this->inventoryStoreManager = $inventory_store_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function allocate(PurchasableEntityInterface $purchasable_entity, $quantity, array $context = []) {
    // Find Item IDs for this purchased entity and store, ordered by preferred
    // locations.
    $inventory_item_ids = $this->inventoryStoreManager->getStoreItemIds($purchasable_entity, $context['commerce_store']);

    $inventory_data = array_map(function ($value) {
      return ['inventory_item_id' => $value];
    }, $inventory_item_ids);

    $placement_array = $this->getInventoryAllocation($inventory_data, $quantity);
    $placement = new InventoryAllocation($placement_array);

    // Return placements if there are any.
    if (!empty($placement->toArray())) {
      return $placement;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies(PurchasableEntityInterface $purchasable_entity, array $context = []) {
    return (array_key_exists('commerce_store', $context) && $context['commerce_store'] instanceof Store);
  }

}
