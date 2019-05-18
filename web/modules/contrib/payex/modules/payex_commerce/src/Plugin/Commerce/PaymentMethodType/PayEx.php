<?php

namespace Drupal\payex_commerce\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the PayEx payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "payex",
 *   label = @Translation("PayEx"),
 *   create_label = @Translation("PayEx"),
 * )
 */
class PayEx extends PaymentMethodTypeBase  {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    return 'PayEx';
  }

}
