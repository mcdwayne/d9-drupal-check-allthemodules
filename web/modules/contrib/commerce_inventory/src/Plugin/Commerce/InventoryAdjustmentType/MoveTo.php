<?php

namespace Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType;

/**
 * Provides an adjustment-type for moving inventory to a location.
 *
 * @CommerceInventoryAdjustmentType(
 *   id = "move_to",
 *   label = @Translation("Move to"),
 *   label_preposition = @Translation("to"),
 *   label_related_preposition = @Translation("from"),
 *   label_sentence_template = "Move @purchasable_entity to @location from @related_location",
 *   label_verb = @Translation("Move"),
 *   related_adjustment_type = "move_from"
 * )
 */
class MoveTo extends IncreaseInventory implements InventoryAdjustmentTypeInterface {

}
