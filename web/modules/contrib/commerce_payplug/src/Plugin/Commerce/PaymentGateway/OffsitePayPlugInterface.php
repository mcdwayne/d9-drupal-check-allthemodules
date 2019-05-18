<?php

namespace Drupal\commerce_payplug\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for the PayPlug payment gateway.
 */
interface OffsitePayPlugInterface extends OffsitePaymentGatewayInterface, SupportsRefundsInterface {

}
