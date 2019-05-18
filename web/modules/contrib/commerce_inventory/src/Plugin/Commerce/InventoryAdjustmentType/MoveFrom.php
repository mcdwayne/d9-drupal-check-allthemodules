<?php

namespace Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType;

/**
 * Provides an adjustment-type for moving inventory from a location.
 *
 * @CommerceInventoryAdjustmentType(
 *   id = "move_from",
 *   label = @Translation("Move from"),
 *   label_preposition = @Translation("from"),
 *   label_related_preposition = @Translation("to"),
 *   label_sentence_template = "Move @purchasable_entity from @location to @related_location",
 *   label_verb = @Translation("Move"),
 *   related_adjustment_type = "move_to"
 * )
 */
class MoveFrom extends DecreaseInventory implements InventoryAdjustmentTypeInterface {

}
