<?php

namespace Drupal\commerce_inventory;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_inventory\InventoryAllocator\InventoryAllocatorInterface;

/**
 * Runs allocators one by one until one of them allocates the inventory.
 */
interface InventoryAllocationManagerInterface {

  /**
   * Adds an allocator.
   *
   * @param \Drupal\commerce_inventory\InventoryAllocator\InventoryAllocatorInterface $allocator
   *   The allocator.
   */
  public function addAllocator(InventoryAllocatorInterface $allocator);

  /**
   * Gets all added allocators.
   *
   * @return \Drupal\commerce_inventory\InventoryAllocator\InventoryAllocatorInterface[]
   *   The allocators.
   */
  public function getAllocators();

  /**
   * Allocates inventory adjustments.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   * @param float $quantity
   *   The amount of inventory to allocate.
   * @param array $context
   *   An array of additional information to give context to the inventory
   *   allocation.
   *
   * @return \Drupal\commerce_inventory\InventoryAllocator\InventoryAllocation
   *   An InventoryAllocation object.
   */
  public function allocate(PurchasableEntityInterface $purchasable_entity, $quantity, array $context = []);

}
