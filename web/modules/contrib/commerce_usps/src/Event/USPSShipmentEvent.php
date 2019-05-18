<?php

namespace Drupal\commerce_usps\Event;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Symfony\Component\EventDispatcher\Event;
use USPS\RatePackage;

/**
 * Rate request event for USPS.
 */
class USPSShipmentEvent extends Event {

  /**
   * The rate package.
   *
   * @var \USPS\RatePackage
   */
  protected $usps_package;

  /**
   * The commerce shipment.
   *
   * @var \Drupal\commerce_shipping\Entity\ShipmentInterface
   */
  protected $commerce_shipment;

  /**
   * RateRequestEvent constructor.
   *
   * @param \USPS\RatePackage $usps_package
   *   The USPS rate package object.
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $commerce_shipment
   *   The Commerce Shipment entity.
   */
  public function __construct(RatePackage $usps_package, ShipmentInterface $commerce_shipment) {
    $this->usps_package = $usps_package;
    $this->commerce_shipment = $commerce_shipment;
  }

  /**
   * Get the rate package.
   *
   * @return \USPS\RatePackage
   *   The rate request object.
   */
  public function getPackage() {
    return $this->usps_package;
  }

  /**
   * Set the rate package.
   *
   * @param \USPS\RatePackage $usps_package
   *   The USPS package object.
   */
  public function setPackage(RatePackage $usps_package) {
    $this->usps_package = $usps_package;
  }

  /**
   * Gets the shipment.
   *
   * @return \Drupal\commerce_shipping\Entity\ShipmentInterface
   *   The shipment.
   */
  public function getShipment() {
    return $this->commerce_shipment;
  }

}
