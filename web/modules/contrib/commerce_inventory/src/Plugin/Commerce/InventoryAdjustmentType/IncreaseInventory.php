<?php

namespace Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType;

/**
 * Provides a generic adjustment-type for increasing inventory.
 *
 * @CommerceInventoryAdjustmentType(
 *   id = "increase",
 *   label = @Translation("Increase")
 * )
 */
class IncreaseInventory extends InventoryAdjustmentTypeBase implements InventoryAdjustmentTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function adjustQuantity($quantity) {
    // If-check and negative multiplier are faster than abs()
    if ($quantity < 0) {
      return $quantity * -1;
    }
    return $quantity;
  }

}
