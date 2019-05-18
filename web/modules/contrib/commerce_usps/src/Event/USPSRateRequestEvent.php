<?php

namespace Drupal\commerce_usps\Event;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Symfony\Component\EventDispatcher\Event;
use USPS\Rate;

/**
 * Rate request event for USPS.
 */
class USPSRateRequestEvent extends Event {

  /**
   * The rate request object.
   *
   * @var \USPS\Rate
   */
  protected $rateRequest;

  /**
   * The shipment being requested.
   *
   * @var \Drupal\commerce_shipping\Entity\ShipmentInterface
   *   The commerce shipment entity.
   */
  protected $shipment;

  /**
   * RateRequestEvent constructor.
   *
   * @param \USPS\Rate $rate_request
   *   The USPS rate request object.
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The Commerce Shipment entity.
   */
  public function __construct(Rate $rate_request, ShipmentInterface $shipment) {
    $this->rateRequest = $rate_request;
    $this->shipment = $shipment;
  }

  /**
   * Gets the rate request object.
   *
   * @return \USPS\Rate
   *   The rate request object.
   */
  public function getRateRequest() {
    return $this->rateRequest;
  }

  /**
   * Set the rate request.
   *
   * @param \USPS\Rate $rate_request
   *   The USPS rate object.
   */
  public function setRateRequest(Rate $rate_request) {
    $this->rateRequest = $rate_request;
  }

  /**
   * Gets the shipment.
   *
   * @return \Drupal\commerce_shipping\Entity\ShipmentInterface
   *   The shipment.
   */
  public function getShipment() {
    return $this->shipment;
  }

}
