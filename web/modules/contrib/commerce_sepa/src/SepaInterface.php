<?php

namespace Drupal\commerce_sepa;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsUpdatingStoredPaymentMethodsInterface;

/**
 * Provides the interface for the commerce_sepa payment gateway.
 */
interface SepaInterface extends SupportsStoredPaymentMethodsInterface, SupportsUpdatingStoredPaymentMethodsInterface {

}
