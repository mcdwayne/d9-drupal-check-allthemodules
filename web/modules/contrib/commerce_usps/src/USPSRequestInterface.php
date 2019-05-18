<?php

namespace Drupal\commerce_usps;

/**
 * Interface for the USPS API Service.
 *
 * @package Drupal\commerce_usps
 */
interface USPSRequestInterface {

  /**
   * Set the request configuration.
   *
   * @param array $configuration
   *   A configuration array from a CommerceShippingMethod.
   */
  public function setConfig(array $configuration);

}
