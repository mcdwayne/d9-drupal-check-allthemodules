<?php

namespace Drupal\commerce_payjp\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for the Payjp payment gateway.
 */
interface PayjpInterface extends OnsitePaymentGatewayInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface {

  /**
   * Get the Payjp API Public key set for the payment gateway.
   *
   * @return string
   *   The Payjp API public key.
   */
  public function getPublicKey();

}
