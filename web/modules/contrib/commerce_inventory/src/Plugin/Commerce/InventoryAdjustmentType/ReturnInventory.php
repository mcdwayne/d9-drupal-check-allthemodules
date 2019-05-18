<?php

namespace Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType;

/**
 * Provides an adjustment-type for returning inventory.
 *
 * @CommerceInventoryAdjustmentType(
 *   id = "return",
 *   label = @Translation("Return")
 * )
 */
class ReturnInventory extends IncreaseInventory implements InventoryAdjustmentTypeInterface {

}
