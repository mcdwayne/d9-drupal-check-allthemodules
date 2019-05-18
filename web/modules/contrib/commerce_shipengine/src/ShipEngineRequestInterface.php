<?php

namespace Drupal\commerce_shipengine;

interface ShipEngineRequestInterface {

  /**
   * Set the request configuration.
   *
   * @param array $configuration
   *   A configuration array from a CommerceShippingMethod.
   */
  public function setConfig(array $configuration);
}
