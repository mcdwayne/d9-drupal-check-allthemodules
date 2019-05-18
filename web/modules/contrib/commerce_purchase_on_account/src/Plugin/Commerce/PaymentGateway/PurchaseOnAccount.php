<?php

namespace Drupal\commerce_purchase_on_account\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase;

/**
 * Provides the "purchase on account" payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "purchase_on_account",
 *   label = "Purchase on account",
 *   display_label = @Translation("Purchase on account"),
 *   forms = {
 *     "add-payment-method" = "Drupal\purchase_on_account\PluginForm\PurchaseOnAccount\PaymentMethodAddForm",
 *   },
 *   payment_method_types = {"purchase_on_account"},
 * )
 */
class PurchaseOnAccount extends PaymentGatewayBase {

}
