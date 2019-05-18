<?php

namespace Drupal\commerce_nzpost;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
/**
 * Interface RateLookupServiceInterface.
 */
interface RateLookupServiceInterface {

  /**
   * Gets a new rate request.
   *
   * @param order $order
   *
   * @return array
   *   The available rates as an array.
   */
  function getRates(ShipmentInterface $shipping_profile, array $config);

}
