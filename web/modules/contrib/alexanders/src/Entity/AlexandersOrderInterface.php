<?php

namespace Drupal\alexanders\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines function for AlexandersOrder entities.
 *
 * @package Drupal\alexanders\Entity
 */
interface AlexandersOrderInterface extends ContentEntityInterface {

  /**
   * Get items associated with the order.
   *
   * @return array
   *   A collection of Alexander Order Items.
   */
  public function getItems();

  /**
   * Exports the print items as JSON specifically for the API.
   *
   * @return string
   *   JSON containing print items.
   */
  public function exportPrintItems();

  /**
   * Set order items.
   *
   * @param array $items
   *   Array of standardPrintItems.
   *
   * @return $this
   */
  public function setItems(array $items);

  /**
   * Get photobooks associated with the order.
   *
   * @return array
   *   A collection of Alexander Order Photobook.
   */
  public function getPhotobooks();

  /**
   * Set photobook items.
   *
   * @param array $photobooks
   *   Array of photobookItems.
   *
   * @return $this
   */
  public function setPhotobooks(array $photobooks);

  /**
   * Get the shipment object associated with the order.
   *
   * @return object
   *   AlexandersShipment entity.
   */
  public function getShipment();

  /**
   * Sets the shipping method for the order.
   *
   * @param object $shipping
   *   AlexandersShipment entity to associate with order.
   *
   * @return $this
   */
  public function setShipment($shipping);

  /**
   * Gets the rush status of the order.
   *
   * @return bool
   *   Whether or not order is a rush.
   */
  public function getRush();

  /**
   * Set rush status of order.
   *
   * @param bool $rush
   *   Whether this order is actually a rush item.
   *
   * @return $this
   */
  public function setRush($rush);

  /**
   * Get due date for order (e.g when it should be shipped).
   *
   * @return int
   *   Due date of this particular order.
   */
  public function getDue();

  /**
   * Set due date of order.
   *
   * @param int $due
   *   Epoch timestamp of when this order is due.
   *
   * @return $this
   */
  public function setDue($due);

  /**
   * Get inventory items associated with order (non-printed items).
   *
   * @return array
   *   Array of inventory items.
   */
  public function getInventoryItems();

  /**
   * Export the inventory items to JSON.
   *
   * @return string
   *   JSON string of the inventory items.
   */
  public function exportInventoryItems();

  /**
   * Set inventory items (non-printed items).
   *
   * @param array $items
   *   Array of AlexandersInventoryItems.
   *
   * @return $this
   */
  public function setInventoryItems(array $items);

}
