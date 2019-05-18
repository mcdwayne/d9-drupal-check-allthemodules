<?php

namespace Drupal\commerce_admin_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_multi_payment\SupportsMultiplePaymentsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\ManualPaymentGatewayInterface;

/**
 * Provides the base interface for manual payment gateways.
 *
 * Manual payment gateways instruct the customer to pay the store
 * in the real world. The gateway creates a payment entity to allow
 * the merchant to track and record the money flow.
 *
 * Examples: cash on delivery, pay in person, cheque, bank transfer, etc.
 */
interface AdminManualPaymentGatewayInterface extends ManualPaymentGatewayInterface, SupportsMultiplePaymentsInterface {


}
