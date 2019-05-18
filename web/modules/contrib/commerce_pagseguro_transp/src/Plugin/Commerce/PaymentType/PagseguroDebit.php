<?php

namespace Drupal\commerce_pagseguro_transp\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;

/**
 * Provides the default payment type.
 *
 * @CommercePaymentType(
 *   id = "pagseguro_debit",
 *   label = @Translation("Pagseguro Debit"),
 *   workflow = "pagseguro_debit",
 * )
 */
class PagseguroDebit extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

}
