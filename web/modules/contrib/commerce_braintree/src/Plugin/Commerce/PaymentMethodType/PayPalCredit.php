<?php

namespace Drupal\commerce_braintree\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the PayPal Credit payment method type.
 *
 * This is separate from the PayPal payment method type to allow Credit to be
 * its own option at Checkout.
 *
 * @CommercePaymentMethodType(
 *   id = "paypal_credit",
 *   label = @Translation("PayPal Credit"),
 *   create_label = @Translation("New PayPal Credit"),
 * )
 */
class PayPalCredit extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    $args = [
      '@paypal_mail' => $payment_method->paypal_mail->value,
    ];
    return $this->t('PayPal Credit (@paypal_mail)', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['paypal_mail'] = BundleFieldDefinition::create('email')
      ->setLabel(t('PayPal Email'))
      ->setDescription(t('The email address associated with the PayPal account.'))
      ->setRequired(TRUE);

    return $fields;
  }

}
