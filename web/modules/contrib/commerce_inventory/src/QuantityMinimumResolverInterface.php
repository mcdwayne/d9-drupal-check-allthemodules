<?php

namespace Drupal\commerce_inventory;

/**
 * Defines the interface for quantity minimum resolvers.
 */
interface QuantityMinimumResolverInterface {

  /**
   * Allocates inventory adjustments.
   *
   * @param int $inventory_item_id
   *   The Inventory Item entity ID.
   *
   * @return float|int|null
   *   An minimum quantity, if resolved. Otherwise NULL, indicating
   *   that the next resolver should be called.
   */
  public function resolve($inventory_item_id);

}
