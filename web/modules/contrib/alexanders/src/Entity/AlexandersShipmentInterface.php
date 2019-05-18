<?php

namespace Drupal\alexanders\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface AlexandersShipmentInterface.
 *
 * @package Drupal\alexanders\Entity
 */
interface AlexandersShipmentInterface extends ContentEntityInterface {

  /**
   * Get method of shipment.
   *
   * @return string
   *   Shipment method.
   */
  public function getMethod();

  /**
   * Set shipping method string.
   *
   * @param string $method
   *   Shipment method for this order.
   *
   * @return $this
   */
  public function setMethod($method);

  /**
   * Get address for shipment.
   *
   * @return object
   *   Address object.
   */
  public function getAddress();

  /**
   * Set address for this shipment.
   *
   * @param object $address
   *   Address object to associate w/ order.
   *
   * @return $this
   */
  public function setAddress($address);

  /**
   * Get tracking number for shipment, provided by Alexanders.
   *
   * @return string
   *   Tracking number.
   */
  public function getTracking();

  /**
   * Set tracking number for this order.
   *
   * @param string $number
   *   Tracking number (or string) for order.
   *
   * @return $this
   */
  public function setTracking($number);

  /**
   * Get timestamp that order was shipped out.
   *
   * @return int
   *   Epoch timestamp for when order was shipped out.
   */
  public function getTimestamp();

  /**
   * Set timestamp that order was shipped out.
   *
   * @param int $time
   *   Epoch timestamp.
   *
   * @return $this
   */
  public function setTimestamp($time);

  /**
   * Get cost of shipment.
   *
   * @return int
   *   Cost of shipment, / by 10 to get $ amount.
   */
  public function getCost();

  /**
   * Set shipping method string.
   *
   * @param int $cost
   *   Cost of shipment.
   *
   * @return $this
   */
  public function setCost($cost);

  /**
   * Build data array as expected by the API.
   *
   * @return array
   *   Shipping data destined for the API.
   */
  public function export();

}
