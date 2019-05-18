<?php

namespace Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType;

/**
 * Provides a generic adjustment-type for decreasing inventory.
 *
 * @CommerceInventoryAdjustmentType(
 *   id = "decrease",
 *   label = @Translation("Decrease")
 * )
 */
class DecreaseInventory extends InventoryAdjustmentTypeBase implements InventoryAdjustmentTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function adjustQuantity($quantity) {
    // If-check and negative multiplier are faster than abs()
    if ($quantity > 0) {
      return $quantity * -1;
    }
    return $quantity;
  }

}
