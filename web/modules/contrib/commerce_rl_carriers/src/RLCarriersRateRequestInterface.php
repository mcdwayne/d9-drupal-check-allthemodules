<?php

namespace Drupal\commerce_rl_carriers;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface;

/**
 * Interface for the RL Carriers shipping plugin.
 */
interface RLCarriersRateRequestInterface {

  /**
   * Sets configuration for requests.
   *
   * @param array $configuration
   *   A configuration array from a CommerceShippingMethod.
   */
  public function setConfig(array $configuration);

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
