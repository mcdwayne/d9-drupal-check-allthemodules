<?php

namespace Drupal\commerce_ups;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface;

/**
 * Interface to create and return a UPS API shipment object.
 *
 * @package Drupal\commerce_ups
 */
interface UPSShipmentInterface {

  /**
   * Creates and returns a UPS API shipment object.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The shipment.
   * @param \Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface $shipping_method
   *   The shipping method.
   *
   * @return \Ups\Entity\Shipment
   *   A Ups API shipment object.
   */
  public function getShipment(ShipmentInterface $shipment, ShippingMethodInterface $shipping_method);

}
