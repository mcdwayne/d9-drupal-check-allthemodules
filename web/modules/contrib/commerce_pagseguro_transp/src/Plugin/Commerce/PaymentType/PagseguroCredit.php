<?php

namespace Drupal\commerce_pagseguro_transp\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;

/**
 * Provides the default payment type.
 *
 * @CommercePaymentType(
 *   id = "pagseguro_credit",
 *   label = @Translation("Pagseguro Credit Card"),
 *   workflow = "pagseguro_credit",
 * )
 */
class PagseguroCredit extends PaymentTypeBase {

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
    return $this->pluginDefinition['label'];
  }

}
