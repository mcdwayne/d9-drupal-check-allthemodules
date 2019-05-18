<?php

namespace Drupal\commerce_purchase_order\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;

/**
 * Provides the Purchase Order payment type.
 *
 * @CommercePaymentType(
 *   id = "payment_purchase_order",
 *   label = @Translation("Purchase Order"),
 *   workflow = "payment_purchase_order"
 *
 * )
 */
class PurchaseOrder extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

}
