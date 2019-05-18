<?php

namespace Drupal\commerce_inventory\Entity\Storage;

use Drupal\commerce_inventory\Entity\InventoryItemInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the storage handler class for Commerce Inventory Adjustment entities.
 *
 * This extends the base storage class, adding required special handling for
 * Commerce Inventory Adjustment entities.
 *
 * @ingroup commerce_inventory
 */
interface InventoryAdjustmentStorageInterface extends ContentEntityStorageInterface {

  /**
   * Create an adjustment for an Inventory Item.
   *
   * @param string $adjustment_type_id
   *   The string id of the type of adjustment.
   * @param \Drupal\commerce_inventory\Entity\InventoryItemInterface $item
   *   Adjust this Inventory Item's quantity.
   * @param float $quantity
   *   The quantity to adjust.
   * @param array $values
   *   Additional field values to use on this adjustment.
   * @param \Drupal\commerce_inventory\Entity\InventoryItemInterface|null $related_item
   *   A related Inventory Item to be used for related adjustment types; IE
   *   move_to and move_from.
   * @param bool $save
   *   Save the adjustment before returning.
   *
   * @return \Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface
   *   The created Inventory Adjustment.
   */
  public function createAdjustment($adjustment_type_id, InventoryItemInterface $item, $quantity, array $values = [], InventoryItemInterface $related_item = NULL, $save = TRUE);

  /**
   * Calculate the cached inventory quantity for an Inventory Item.
   *
   * @param int $item_id
   *   The Inventory Item ID to calculate the cached inventory quantity.
   *
   * @return float
   *   The calculated inventory quantity.
   */
  public function calculateQuantity($item_id);

  /**
   * Returns a pre-built select query.
   *
   * The query returns Inventory Item entity ID and quantity using the 'ia'
   * table alias.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The select query instance.
   */
  public function getQuantitySelectQuery();

}
