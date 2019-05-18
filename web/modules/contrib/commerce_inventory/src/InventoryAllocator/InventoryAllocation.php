<?php

namespace Drupal\commerce_inventory\InventoryAllocator;

/**
 * An object to hold allocation of Inventory Items for specific quantities.
 */
class InventoryAllocation implements \ArrayAccess, \IteratorAggregate {

  /**
   * Stores the inventory adjustment allocation data.
   *
   * @var array
   */
  protected $storage = [];

  /**
   * Constructs an InventoryAllocation object.
   *
   * @param array $inventory_allocations
   *   An array of Inventory Item IDs and quantity pairs in order.
   */
  public function __construct(array $inventory_allocations = []) {
    foreach ($inventory_allocations as $allocation) {
      if (array_key_exists('inventory_item_id', $allocation) && array_key_exists('quantity', $allocation)) {
        if (is_string($allocation['inventory_item_id']) || is_int($allocation['inventory_item_id'])) {
          $this->addAllocation($allocation['inventory_item_id'], $allocation['quantity']);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($index) {
    if (isset($this->storage[$index])) {
      return $this->storage[$index];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($index, $newval) {
    if (array_key_exists('inventory_item_id', $newval) && array_key_exists('quantity', $newval)) {
      if (is_string($newval['inventory_item_id']) || is_int($newval['inventory_item_id'])) {
        $this->storage[$index] = [
          'inventory_item_id' => intval($newval['inventory_item_id']),
          'quantity' => floatval($newval['quantity']),
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($index) {
    unset($this->storage[$index]);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($index) {
    return isset($this->storage[$index]);
  }

  /**
   * Implements the magic __clone() method.
   */
  public function __clone() {
    foreach ($this->storage as $index => $value) {
      $this->storage[$index] = clone $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->storage);
  }

  /**
   * Returns all adjustments as an array.
   *
   * @return array
   *   An array of inventory adjustments.
   */
  public function toArray() {
    return $this->storage;
  }

  /**
   * Returns a representation of the object for use in JSON serialization.
   *
   * @return string
   *   The safe string content.
   */
  public function jsonSerialize() {
    return (string) $this;
  }

  /**
   * Add an allocation of an Inventory Item adjustment.
   *
   * @param int $inventory_item_id
   *   The Inventory Item ID.
   * @param float $quantity
   *   The quantity to allocate.
   */
  public function addAllocation($inventory_item_id, $quantity) {
    $this->storage[] = [
      'inventory_item_id' => intval($inventory_item_id),
      'quantity' => floatval($quantity),
    ];
  }

}
