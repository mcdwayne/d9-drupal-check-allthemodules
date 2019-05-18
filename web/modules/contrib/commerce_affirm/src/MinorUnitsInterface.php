<?php

namespace Drupal\commerce_affirm;

use Drupal\commerce_price\Price;

/**
 * Interface for converting to minor currency units.
 *
 * @todo Remove when https://www.drupal.org/project/commerce/issues/2944281 is
 * fixed.
 */
interface MinorUnitsInterface {

  /**
   * Converts a price to minor units.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The price object.
   *
   * @return string
   *   The price number in minor currency units.
   */
  public function toMinorUnits(Price $price);

}
