<?php

namespace Drupal\payex_commerce\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;

/**
 * Provides the interface for the payex payment gateway.
 */
interface PayExInterface extends PaymentGatewayInterface, SupportsStoredPaymentMethodsInterface   {

}
