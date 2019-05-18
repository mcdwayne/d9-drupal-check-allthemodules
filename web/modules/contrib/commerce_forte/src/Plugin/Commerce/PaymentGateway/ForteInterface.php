<?php

namespace Drupal\commerce_forte\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;


/**
 * Provides the interface for the Payflow payment gateway.
 */
interface ForteInterface extends OnsitePaymentGatewayInterface, SupportsAuthorizationsInterface {

}
