<?php

declare(strict_types = 1);

namespace Drupal\commerce_paytrail\Repository\Product;

/**
 * The handling product type.
 */
class HandlingProduct extends ProductBase {

  /**
   * {@inheritdoc}
   */
  public function getType() : int {
    return 3;
  }

}
