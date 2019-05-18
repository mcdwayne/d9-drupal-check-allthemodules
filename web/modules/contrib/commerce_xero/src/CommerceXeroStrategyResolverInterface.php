<?php

namespace Drupal\commerce_xero;

use Drupal\commerce_payment\Entity\PaymentInterface;

/**
 * Describes the methods necessary to resolve strategies.
 */
interface CommerceXeroStrategyResolverInterface {

  /**
   * Finds appropriate commerce xero strategy for an order.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The Commerce payment entity to check.
   *
   * @return \Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface
   *   A strategy interface to use.
   */
  public function resolve(PaymentInterface $payment);

}
