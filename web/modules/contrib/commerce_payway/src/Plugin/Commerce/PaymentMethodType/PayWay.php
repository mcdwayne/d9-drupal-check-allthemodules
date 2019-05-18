<?php

namespace Drupal\commerce_payway\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the PayPal payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "payway",
 *   label = @Translation("Credit card"),
 *   create_label = @Translation("Credit card")
 * )
 */
class PayWay extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    return $this->t('Credit card (authorised)');
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['payway_token'] = BundleFieldDefinition::create('string')
      ->setLabel(t('PayWay Token'))
      ->setDescription(t('The PayWay token associated with the credit card.'))
      ->setRequired(TRUE);

    return $fields;
  }

}
