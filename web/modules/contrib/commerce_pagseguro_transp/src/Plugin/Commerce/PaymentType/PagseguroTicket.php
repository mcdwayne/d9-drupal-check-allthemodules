<?php

namespace Drupal\commerce_pagseguro_transp\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;

/**
 * Provides the manual payment type.
 *
 * @CommercePaymentType(
 *   id = "pagseguro_ticket",
 *   label = @Translation("Bank Ticket"),
 *   workflow = "pagseguro_ticket",
 * )
 */
class PagseguroTicket extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

}
