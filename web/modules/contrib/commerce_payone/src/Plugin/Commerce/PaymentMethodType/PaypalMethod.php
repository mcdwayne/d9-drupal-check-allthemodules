<?php

namespace Drupal\commerce_payone\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the payment method type for Payone Sepa payments.
 *
 * @CommercePaymentMethodType(
 *   id = "commerce_payone_paypal",
 *   label = @Translation("Payone PayPal integration"),
 *   create_label = @Translation("PayPal"),
 * )
 */
class PaypalMethod extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    return $this->t('PayPal');
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    return $fields;
  }

}
