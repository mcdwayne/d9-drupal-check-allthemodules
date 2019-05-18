<?php

declare(strict_types = 1);

namespace Drupal\commerce_paytrail\Repository\Product;

/**
 * The shipping product type.
 */
class ShippingProduct extends ProductBase {

  /**
   * {@inheritdoc}
   */
  public function getType() : int {
    return 2;
  }

}
