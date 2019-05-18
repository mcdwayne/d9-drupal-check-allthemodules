<?php

namespace Drupal\commerce_econt\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface;
use Drupal\commerce_shipping\Entity\ShipmentInterface as ShipInterface;
/**
 * Defines the base interface for Econt shipping method.
 */
interface ShippingMethodEcontInterface extends ShippingMethodInterface{

  /**
   * Gets the data of Defaut Store.
   *
   * @return array
   *   The array with all nessasary data fields.
   */
  public function getDefaultStoreData();

  /**
   * Gets the shipping data.
   *
   * @return array
   *   The array with all nessasary data fields.
   */
  public function getShippingData(ShipInterface $shipment);
}
