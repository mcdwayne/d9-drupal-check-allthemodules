<?php

namespace Drupal\commerce_product_reservation\Exception;

/**
 * Class OutOfStockException.
 */
class OutOfStockException extends \Exception {

  /**
   * The max allowed to order.
   *
   * @var int
   */
  private $max;

  /**
   * Setter.
   */
  public function setMaxQuantity($max) {
    $this->max = $max;
  }

  /**
   * Getter.
   */
  public function getMaxQuantity() {
    return $this->max;
  }

}
