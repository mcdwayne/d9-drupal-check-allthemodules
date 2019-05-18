<?php

namespace Drupal\commerce_ups;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface;

/**
 * The interface for fetching and returning rates using the UPS API.
 *
 * @package Drupal\commerce_ups
 */
interface UPSRateRequestInterface extends UPSRequestInterface {

  /**
   * The name of the logger channel to use throughout this module.
   */
  const LOGGER_CHANNEL = 'commerce_ups';

  /**
   * Fetch rates for the shipping method.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $commerce_shipment
   *   The commerce shipment.
   * @param \Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface $shipping_method
   *   The shipping method.
   *
   * @return array
   *   An array of ShippingRate objects.
   */
  public function getRates(ShipmentInterface $commerce_shipment, ShippingMethodInterface $shipping_method);

}
