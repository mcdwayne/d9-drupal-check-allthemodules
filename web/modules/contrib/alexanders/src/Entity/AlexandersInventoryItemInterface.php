<?php

namespace Drupal\alexanders\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines function for AlexandersInventoryItem entities.
 *
 * @package Drupal\alexanders\Entity
 */
interface AlexandersInventoryItemInterface extends ContentEntityInterface {

  /**
   * Get item's description.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Set item's description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Get item's SKU.
   *
   * @return string
   */
  public function getSku();

  /**
   * Set item's SKU.
   *
   * @return $this
   */
  public function setSku($sku);

  /**
   * Set item's quantity.
   *
   * @return int
   */
  public function getQuantity();

  /**
   * Set item's quantity.
   *
   * @return $this
   */
  public function setQuantity($quantity);
}
