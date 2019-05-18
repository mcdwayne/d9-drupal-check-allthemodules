<?php

namespace Drupal\commerce_clic\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the credit card payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "crypto_currency",
 *   label = @Translation("Crypto currency"),
 * )
 */
class CryptoCurrency extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    return $this->t('Crypto wallet');
  }

}
