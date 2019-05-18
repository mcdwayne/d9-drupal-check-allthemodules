<?php

namespace Drupal\commerce_pagseguro\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;

/**
 * Provides the default payment type.
 *
 * @CommercePaymentType(
 *   id = "pagseguro_credit_card",
 *   label = @Translation("Credit Card via Checkout Transparente"),
 *   workflow = "pagseguro_credit_card",
 * )
 */
class PagseguroCreditCard extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    dsm($this->pluginDefinition['label'], 'getLabel');

    return $this->pluginDefinition['label'];
  }

}
