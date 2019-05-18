<?php

namespace Drupal\commerce_pagseguro\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;

/**
 * Provides the manual payment type.
 *
 * @CommercePaymentType(
 *   id = "pagseguro_lightbox",
 *   label = @Translation("Pagseguro Lightbox"),
 *   workflow = "pagseguro_lightbox",
 * )
 */
class PagseguroLightbox extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

}
