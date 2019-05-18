<?php

namespace Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType;

/**
 * Provides an adjustment-type for manually increasing to specific amount.
 *
 * @CommerceInventoryAdjustmentType(
 *   id = "manual",
 *   label = @Translation("Manual"),
 *   label_verb = @Translation("Manually adjust")
 * )
 */
class Manual extends InventoryAdjustmentTypeBase implements InventoryAdjustmentTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function adjustQuantity($quantity, $current_quantity = NULL) {
    // Make sure new quantity positive.
    if ($quantity < 0) {
      $quantity = $quantity * -1;
    }
    // Clean current quantity.
    $current_quantity = (is_int($current_quantity) || is_float($current_quantity)) ? $current_quantity : 0;

    // Find quantity difference for adjustment.
    return $quantity - $current_quantity;
  }

}
