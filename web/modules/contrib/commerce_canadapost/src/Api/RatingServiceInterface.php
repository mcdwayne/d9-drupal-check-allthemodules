<?php

namespace Drupal\commerce_canadapost\Api;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface;

/**
 * Defines the interface for the Rating API integration service.
 */
interface RatingServiceInterface {

  /**
   * Get rates from the Canada Post API.
   *
   * @param \Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface $shipping_method
   *   The shipping method.
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The shipment.
   * @param array $options
   *   The options array.
   *
   * @return \Drupal\commerce_shipping\ShippingRate[]
   *   The rates returned by Canada Post.
   */
  public function getRates(ShippingMethodInterface $shipping_method, ShipmentInterface $shipment, array $options);

}
