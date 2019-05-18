<?php

namespace Drupal\commerce_shipengine;

use Drupal\commerce_shipping\Entity\ShipmentInterface;

/**
 * ShipEngine API service.
 *
 * @package Drupal\commerce_shippping
 */
abstract class ShipEngineRequest implements ShipEngineRequestInterface {
  /**
   * @var array
   */
  protected $configuration;

  /**
   * @var \Drupal\commerce_shipping\Entity\ShipmentInterface
   */
  protected $commerce_shipment;

  /**
   * Sets configuration for requests.
   *
   * @param array $configuration
   *   A configuration array from a CommerceShippingMethod.
   */
  public function setConfig(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * Gets configuration for requests.
   */
  public function getConfig() {
    return $this->configuration;
  }

  /**
   * Set the shipment for rate requests.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $commerce_shipment
   *   A Drupal Commerce shipment entity.
   */
  public function setShipment(ShipmentInterface $commerce_shipment) {
    $this->commerce_shipment = $commerce_shipment;
  }

}
