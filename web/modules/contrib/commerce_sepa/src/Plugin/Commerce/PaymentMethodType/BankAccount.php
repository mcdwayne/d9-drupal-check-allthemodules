<?php

namespace Drupal\commerce_sepa\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the credit card payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "bank_account",
 *   label = @Translation("Bank account"),
 *   create_label = @Translation("New bank account"),
 * )
 */
class BankAccount extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    $t_args = [
      '@account_number' => substr($payment_method->iban->value, -4),
    ];

    return $this->t('Bank account ending in @account_number', $t_args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['iban'] = BundleFieldDefinition::create('string')
      ->setLabel(t('IBAN'))
      ->setDescription(t('The International Bank Account Number.'))
      ->setSetting('max_length', 34)
      ->setRequired(TRUE);

    return $fields;
  }

}
