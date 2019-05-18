<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Interface to resolve locale for given customer.
 */
interface LocaleResolverInterface {

  /**
   * Attempts to resolve RFC 1766 locale.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return string
   *   The locale.
   */
  public function resolve(OrderInterface $order) : string;

}
