<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data\Order;

use Drupal\commerce_klarna_payments\Klarna\Data\ObjectInterface;

/**
 * An interface to describe order request.
 */
interface RequestInterface extends ObjectInterface {

  /**
   * Sets the order id.
   *
   * @param string $orderId
   *   The order id.
   *
   * @return $this
   *   The self.
   */
  public function setOrderId(string $orderId) : RequestInterface;

}
