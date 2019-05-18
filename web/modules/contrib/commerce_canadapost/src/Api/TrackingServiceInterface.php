<?php

namespace Drupal\commerce_canadapost\Api;

use Drupal\commerce_shipping\Entity\ShipmentInterface;

/**
 * Defines the interface for the Tracking API integration service.
 */
interface TrackingServiceInterface {

  /**
   * Fetches the tracking number for the given tracking PIN.
   *
   * @param string $tracking_pin
   *   The tracking PIN for which to fetch the tracking number.
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The shipment for which we're fetching the tracking details for.
   *
   * @return array
   *   The tracking summary for the specified pin.
   */
  public function fetchTrackingSummary($tracking_pin, ShipmentInterface $shipment);

}
