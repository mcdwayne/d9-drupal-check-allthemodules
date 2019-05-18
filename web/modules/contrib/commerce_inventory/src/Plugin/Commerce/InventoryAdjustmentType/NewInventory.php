<?php

namespace Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType;

/**
 * Provides an adjustment-type for receiving new inventory.
 *
 * @CommerceInventoryAdjustmentType(
 *   id = "new",
 *   label = @Translation("New"),
 *   label_verb = @Translation("Add"),
 * )
 */
class NewInventory extends IncreaseInventory implements InventoryAdjustmentTypeInterface {

}
