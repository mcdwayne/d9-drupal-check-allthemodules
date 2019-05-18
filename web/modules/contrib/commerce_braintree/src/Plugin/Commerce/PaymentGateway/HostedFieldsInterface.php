<?php

namespace Drupal\commerce_braintree\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for the HostedFields payment gateway.
 */
interface HostedFieldsInterface extends OnsitePaymentGatewayInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface {

  /**
   * Generates the client token.
   *
   * Used by the add-payment-method plugin form.
   *
   * @return string
   *   The client token.
   */
  public function generateClientToken();

}
