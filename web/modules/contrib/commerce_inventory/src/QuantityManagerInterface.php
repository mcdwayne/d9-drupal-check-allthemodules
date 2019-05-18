<?php

namespace Drupal\commerce_inventory;

/**
 * Defines the interface for inventory quantity managers.
 */
interface QuantityManagerInterface {

  /**
   * Gets an Inventory Item's quantity.
   *
   * @param int $inventory_item_id
   *   The Inventory Item entity ID.
   *
   * @return float
   *   The quantity.
   */
  public function getQuantity($inventory_item_id);

  /**
   * Invalidates the current quantity cache.
   *
   * @param int $inventory_item_id
   *   The ID of the Inventory Item entity to invalidate cache on.
   */
  public function invalidateQuantity($inventory_item_id);

}
