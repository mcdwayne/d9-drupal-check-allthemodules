<?php

namespace Drupal\commerce_purchase_on_account\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the "Purchase on account" payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "purchase_on_account",
 *   label = @Translation("Purchase on account"),
 *   create_label = @Translation("Purchase on account"),
 * )
 */
class PurchaseOnAccount extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    return $this->t('Purchase on account');
  }

}
