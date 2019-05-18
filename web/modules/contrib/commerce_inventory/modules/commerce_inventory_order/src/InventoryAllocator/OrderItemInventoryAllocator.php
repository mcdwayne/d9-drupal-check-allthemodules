<?php

namespace Drupal\commerce_inventory_order\InventoryAllocator;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_inventory\InventoryAllocator\InventoryAllocation;
use Drupal\commerce_inventory\InventoryAllocator\InventoryAllocatorBase;
use Drupal\commerce_inventory\QuantityManagerInterface;
use Drupal\commerce_inventory_order\InventoryOrderManager;
use Drupal\commerce_inventory_store\InventoryStoreManager;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_store\Entity\Store;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Returns the Order Item inventory allocator.
 */
class OrderItemInventoryAllocator extends InventoryAllocatorBase {

  /**
   * The Inventory Adjustment entity storage.
   *
   * @var \Drupal\commerce_inventory\Entity\Storage\InventoryAdjustmentStorageInterface
   */
  protected $inventoryAdjustmentStorage;

  /**
   * The inventory Commerce Store manager.
   *
   * @var \Drupal\commerce_inventory_store\InventoryStoreManager
   */
  protected $inventoryStoreManager;

  /**
   * The Order Item entity storage.
   *
   * @var \Drupal\commerce_order\OrderItemStorageInterface
   */
  protected $orderItemStorage;

  /**
   * Constructs a new StoreInventoryPlacementResolver object.
   *
   * @param \Drupal\commerce_inventory\QuantityManagerInterface $quantity_available
   *   The quantity available manager.
   * @param \Drupal\commerce_inventory\QuantityManagerInterface $quantity_minimum
   *   The quantity minimum manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_inventory_store\InventoryStoreManager $inventory_store_manager
   *   The inventory Commerce Store manager.
   */
  public function __construct(QuantityManagerInterface $quantity_available, QuantityManagerInterface $quantity_minimum, EntityTypeManagerInterface $entity_type_manager, InventoryStoreManager $inventory_store_manager) {
    parent::__construct($quantity_available, $quantity_minimum);
    $this->inventoryAdjustmentStorage = $entity_type_manager->getStorage('commerce_inventory_adjustment');
    $this->inventoryStoreManager = $inventory_store_manager;
    $this->orderItemStorage = $entity_type_manager->getStorage('commerce_order_item');
  }

  /**
   * {@inheritdoc}
   */
  public function allocate(PurchasableEntityInterface $purchasable_entity, $quantity, array $context = []) {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $context['commerce_order_item'];

    /** @var \Drupal\commerce_store\Entity\StoreInterface $store */
    $store = $context['commerce_store'];

    // Get Current Order Item adjustment holds.
    $adjust_manually = InventoryOrderManager::isAdjustedManually($order_item);
    $holds = $order_item->get('inventory_adjustment_holds')->getValue();
    $holds_by_item = array_column($holds, 'quantity', 'target_id');

    // Load unmodified Order Item to check against changes.
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface|null $unmodified_order_item */
    $unmodified_order_item = (!is_null($order_item->id())) ? $this->orderItemStorage->load($order_item->id()) : NULL;
    $unmodified_holds_by_item = [];
    if (!is_null($unmodified_order_item)) {
      // Unmodified item adjustment holds.
      $unmodified_holds_by_item = array_column($unmodified_order_item->get('inventory_adjustment_holds')->getValue(), 'quantity', 'target_id');
    }

    // Find Item IDs for this purchased entity and store, ordered by preferred
    // locations.
    $inventory_item_ids = $this->inventoryStoreManager->getStoreItemIds($purchasable_entity, $store);

    // Validate manual allotment order.
    if ($adjust_manually) {
      // Re-order preferred inventory-item order in reverse (that way, the first
      // item will end up first in the new order).
      foreach (array_reverse($holds) as $hold) {
        $item_key = array_search($hold['target_id'], $inventory_item_ids);
        // If item key is found, remove and add to the beginning of the array.
        if ($item_key !== FALSE) {
          unset($inventory_item_ids[$item_key]);
          array_unshift($inventory_item_ids, $hold['target_id']);
        }
      }
    }

    // Get previous adjustments. Allows for prioritizing restocked inventory
    // back to the original locations.
    $adjustments = [];
    if (array_key_exists('inventory_adjustments', $context) && is_array($context['inventory_adjustments'])) {
      $adjustments = array_column($context['inventory_adjustments'], 'quantity', 'inventory_item_id');
    }

    // Build inventory data array.
    $inventory_data = [];
    foreach ($inventory_item_ids as $key => $inventory_item_id) {
      $data_item = [];
      $data_item['inventory_item_id'] = $inventory_item_id;

      // Set specific quantity.
      if ($adjust_manually && array_key_exists($inventory_item_id, $holds_by_item)) {
        $data_item['quantity'] = $holds_by_item[$inventory_item_id];
      }

      // Track previous quantity.
      if (array_key_exists($inventory_item_id, $holds_by_item)) {
        $data_item['previous_quantity'] = floatval($unmodified_holds_by_item[$inventory_item_id]);
      }

      // Inventory needs to be returned and previous Inventory Adjustments exist
      // for this Inventory Item.
      if (!$adjust_manually && $quantity < 0 && array_key_exists($inventory_item_id, $adjustments)) {
        $data_item['min_quantity'] = floatval($adjustments[$inventory_item_id]);

        // Prepend to inventory data array. This will add items in order of
        // reverse precedence for multiple previous Inventory Adjustments.
        array_unshift($inventory_data, $data_item);
      }
      // Add as regular option to inventory data.
      else {
        $inventory_data[] = $data_item;
      }
    }

    // Get Inventory allocation.
    $allocation_array = $this->getInventoryAllocation($inventory_data, $quantity);
    $allocation = new InventoryAllocation($allocation_array);

    // Return allocations if there are any.
    if (!empty($allocation->toArray())) {
      return $allocation;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies(PurchasableEntityInterface $purchasable_entity, array $context = []) {
    $order_item_exists = (array_key_exists('commerce_order_item', $context) && $context['commerce_order_item'] instanceof OrderItemInterface);
    $store_exists = (array_key_exists('commerce_store', $context) && $context['commerce_store'] instanceof Store);
    return ($order_item_exists && $store_exists);
  }

}
