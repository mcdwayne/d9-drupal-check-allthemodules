<?php

namespace Drupal\commerce_inventory;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_inventory\InventoryAllocator\InventoryAllocation;
use Drupal\commerce_inventory\InventoryAllocator\InventoryAllocatorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides a manager for allocating inventory adjustments.
 */
class InventoryAllocationManager implements InventoryAllocationManagerInterface {

  /**
   * The allocators.
   *
   * @var \Drupal\commerce_inventory\InventoryAllocator\InventoryAllocatorInterface[]
   */
  protected $allocators;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new AdjustmentAllocationManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function addAllocator(InventoryAllocatorInterface $allocator) {
    $this->allocators[] = $allocator;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllocators() {
    return $this->allocators;
  }

  /**
   * Runs through each allocator, returning the first proper allocation.
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
  protected function doAllocate(PurchasableEntityInterface $purchasable_entity, $quantity, array $context = []) {
    foreach ($this->allocators as $allocator) {
      if ($allocator->applies($purchasable_entity, $context)) {
        $allocation = $allocator->allocate($purchasable_entity, $quantity, $context);
        if ($allocation) {
          return $allocation;
        }
      }
    }

    // Default with an empty allocation.
    return new InventoryAllocation();
  }

  /**
   * {@inheritdoc}
   */
  public function allocate(PurchasableEntityInterface $purchasable_entity, $quantity, array $context = []) {
    $allocation = $this->doAllocate($purchasable_entity, $quantity, $context);

    // Add items to context.
    $context['quantity'] = $quantity;
    $context['purchasable_entity'] = $purchasable_entity;

    // Allow modules to alter the allocation.
    $this->moduleHandler->alter('commerce_inventory_allocation', $allocation, $context);

    return $allocation;
  }

}
