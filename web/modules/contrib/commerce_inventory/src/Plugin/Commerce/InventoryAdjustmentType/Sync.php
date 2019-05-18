<?php

namespace Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType;

/**
 * Provides an adjustment-type for providers to update to specific amount.
 *
 * @CommerceInventoryAdjustmentType(
 *   id = "sync",
 *   label = @Translation("Sync"),
 *   label_verb = @Translation("Sync"),
 *   internal = TRUE
 * )
 */
class Sync extends Manual implements InventoryAdjustmentTypeInterface {

  // Nothing to see here.
}
