<?php

namespace Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType;

/**
 * Provides an adjustment-type for selling inventory.
 *
 * @CommerceInventoryAdjustmentType(
 *   id = "sell",
 *   label = @Translation("Sell")
 * )
 */
class SellInventory extends DecreaseInventory implements InventoryAdjustmentTypeInterface {

}
