<?php

namespace Drupal\commerce_usps\Event;

/**
 * Defines events for the Commerce USPS module.
 */
final class USPSEvents {

  /**
   * Event name to alter rate requests.
   */
  const BEFORE_RATE_REQUEST = 'commerce_usps.before_rate_request';

  /**
   * Event name to alter built shipments.
   */
  const AFTER_BUILD_SHIPMENT = 'commerce_usps.after_build_shipment';

}
