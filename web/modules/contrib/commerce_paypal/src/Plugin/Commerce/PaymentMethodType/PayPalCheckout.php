<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the PayPal Checkout payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "paypal_checkout",
 *   label = @Translation("PayPal Checkout"),
 *   create_label = @Translation("PayPal"),
 * )
 */
class PayPalCheckout extends PaymentMethodTypeBase {

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

    $fields['flow'] = BundleFieldDefinition::create('string')
      ->setLabel($this->t('Flow'))
      ->setDescription($this->t('The flow (e.g "shortcut" or "mark").'))
      ->setDisplayConfigurable('form', FALSE)
      ->setRequired(TRUE);

    return $fields;
  }

}
