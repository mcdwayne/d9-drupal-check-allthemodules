<?php

namespace Drupal\commerce_paymetric\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for the Paymetric payment gateway.
 */
interface PaymetricInterface extends OnsitePaymentGatewayInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface {
    //public function createPayment(PaymentInterface $payment, $capture = TRUE); // Change this to FALSE to NOT capture last 4 digits of card ?
}
