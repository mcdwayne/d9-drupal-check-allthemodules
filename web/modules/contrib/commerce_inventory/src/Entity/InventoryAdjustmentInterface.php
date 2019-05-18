<?php

namespace Drupal\commerce_inventory\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\core_extend\Entity\EntityCreatedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Inventory Adjustment entities.
 *
 * @ingroup commerce_inventory
 */
interface InventoryAdjustmentInterface extends ContentEntityInterface, EntityCreatedInterface, EntityOwnerInterface {

  /**
   * Gets an adjustment data value with the given key.
   *
   * Used to store additional data..
   *
   * @param string $key
   *   The key.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The value.
   */
  public function getData($key, $default = NULL);

  /**
   * Sets an adjustment data value with the given key.
   *
   * @param string $key
   *   The key.
   * @param mixed $value
   *   The value.
   *
   * @return $this
   */
  public function setData($key, $value);

  /**
   * A description of the adjustment.
   *
   * @param bool $link
   *   Link the replacements in the description.
   *
   * @return string
   *   The description.
   */
  public function getDescription($link = TRUE);

  /**
   * Sets this adjustments related Inventory Item id.
   *
   * @param int $item_id
   *   The Inventory Item id to relate to this adjustment.
   *
   * @return $this
   *   Return this object.
   */
  public function setItemId($item_id);

  /**
   * Get this adjustments related Inventory Item id.
   *
   * @return int|null
   *   The related Inventory Item id.
   */
  public function getItemId();

  /**
   * Sets this adjustments related Inventory Item entity.
   *
   * @param InventoryItemInterface $item
   *   The Inventory Item to relate to this adjustment.
   *
   * @return $this
   *   Return this object.
   */
  public function setItem(InventoryItemInterface $item);

  /**
   * Get this adjustments related Inventory Item entity.
   *
   * @return InventoryItemInterface|null
   *   The related Inventory Item entity.
   */
  public function getItem();

  /**
   * Get this adjustments related Location entity through the Inventory Item.
   *
   * @return InventoryItemInterface|null
   *   The Inventory Item's Location entity.
   */
  public function getLocation();

  /**
   * Get this adjustments related Purchasable Entity through the Inventory Item.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface|null
   *   The Inventory Item's Purchasable Entity.
   */
  public function getPurchasableEntity();

  /**
   * Set the quantity of the inventory adjustment.
   *
   * @param float $quantity
   *   The adjustment quantity to be formatted by the selected adjustment type.
   *
   * @return $this
   *   Return this object.
   */
  public function setQuantity($quantity);

  /**
   * Gets the formatted quantity of the inventory adjustment.
   *
   * @return float
   *   The quantity of the adjustment.
   */
  public function getQuantity();

  /**
   * This adjustment has a related adjustment.
   *
   * @return bool
   *   True if this adjustment has a related adjustment. False otherwise.
   */
  public function hasRelatedAdjustment();

  /**
   * Relate another Inventory Adjustment to this adjustment.
   *
   * @param int $adjustment_id
   *   The related Adjustment's entity id.
   *
   * @return $this
   *   Return this object.
   */
  public function setRelatedAdjustmentId($adjustment_id);

  /**
   * Gets the Inventory Adjustment entity id related to this adjustment.
   *
   * @return int|null
   *   The Inventory Adjustment ID, or NULL.
   */
  public function getRelatedAdjustmentId();

  /**
   * Relate another Inventory Adjustment to this adjustment.
   *
   * @param InventoryAdjustmentInterface $adjustment
   *   The related Inventory Adjustment entity.
   *
   * @return $this
   *   Return this object.
   */
  public function setRelatedAdjustment(InventoryAdjustmentInterface $adjustment);

  /**
   * Gets the Inventory Adjustment entity related to this adjustment.
   *
   * @return InventoryAdjustmentInterface|null
   *   The Adjustment entity, or NULL.
   */
  public function getRelatedAdjustment();

  /**
   * Gets the Inventory Adjustment type.
   *
   * @return \Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType\InventoryAdjustmentTypeInterface
   *   The Inventory Adjustment type.
   */
  public function getType();

}
