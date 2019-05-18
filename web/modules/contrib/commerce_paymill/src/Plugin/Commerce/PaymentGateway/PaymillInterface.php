<?php

namespace Drupal\commerce_paymill\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_price\Price;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for the Paymill payment gateway.
 */
interface PaymillInterface extends OnsitePaymentGatewayInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface {

  /**
   * Get Paymill public key for mode selected in the payment gateway.
   */
  public function getPaymillPublicKey();

  /**
   * Returns the integer charge amount for paymill.
   *
   * @param Price $amount
   *   The amount being charged.
   *
   * @return integer
   *   The Paymill formatted amount.
   */
  public function amountGetInteger(Price $amount);

}
