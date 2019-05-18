<?php

namespace Drupal\commerce_pagseguro\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;

/**
 * Provides the manual payment type.
 *
 * @CommercePaymentType(
 *   id = "pagseguro_boleto",
 *   label = @Translation("Pagseguro Boleto"),
 *   workflow = "pagseguro_boleto",
 * )
 */
class PagseguroBoleto extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

}
