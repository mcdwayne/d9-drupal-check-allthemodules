<?php

namespace Drupal\commerce_funds\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;

/**
 * Provides the interface for the Funds balance payment gateway.
 */
interface BalanceGatewayInterface extends OnsitePaymentGatewayInterface {

  /**
   * Gets the payment gateway currency.
   *
   * @return \Drupal\commerce_price\Entity\Currency
   *   The payment gateway currency.
   */
  public function getCurrency();

  /**
   * Gets the payment gateway balance id.
   *
   * @return int
   *   The payment gateway balance id.
   */
  public function getBalanceId();

}
