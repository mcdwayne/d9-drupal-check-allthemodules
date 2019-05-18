<?php

namespace Drupal\commerce_inventory\InventoryAllocator;

use Drupal\commerce\PurchasableEntityInterface;

/**
 * Defines the interface for inventory adjustment allocators.
 */
interface InventoryAllocatorInterface {

  /**
   * Determines whether the allocator applies to the given circumstance.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   * @param array $context
   *   An array of additional information.
   *
   * @return bool
   *   TRUE if the allocator applies, FALSE otherwise.
   */
  public function applies(PurchasableEntityInterface $purchasable_entity, array $context = []);

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
   *   An InventoryAllocation object, if resolved. Otherwise NULL, indicating
   *   that the next allocator should be called.
   */
  public function allocate(PurchasableEntityInterface $purchasable_entity, $quantity, array $context = []);

}
