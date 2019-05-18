<?php

namespace Drupal\commerce_purchase_order\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the Purchase Order payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "purchase_order",
 *   label = @Translation("Purchase Order"),
 *   create_label = @Translation("New Purchase Order"),
 *
 * )
 */
class PurchaseOrder extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    $po_number = NULL;
    if ($payment_method->hasField('po_number')) {
      $po_number = $payment_method->po_number->value;
    }
    $placeholders = [
      '@po_number' => $po_number,
    ];
    return $this->t('Purchase Order# @po_number', $placeholders);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['po_number'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Purchase Order Number'))
      ->setDescription(t('The number assigned to your purchase order.'))
      ->setRequired(TRUE);
    return $fields;
  }

}
