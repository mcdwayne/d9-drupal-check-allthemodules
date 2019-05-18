<?php

namespace Drupal\commerce_worldline\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the SIPS payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "sips",
 *   label = @Translation("SIPS account"),
 *   create_label = @Translation("New SIPS account"),
 * )
 */
class SIPS extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    return $this->t('Worldline payment');
  }

}
