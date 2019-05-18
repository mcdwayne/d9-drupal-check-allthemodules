<?php

namespace Drupal\commerce_iats\Plugin\Commerce\PaymentMethodType;

use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;

/**
 * Provides the ACH payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "commerce_iats_ach",
 *   label = @Translation("ACH"),
 *   create_label = @Translation("New bank account"),
 * )
 */
class CommerceIatsAch extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    $args = [
      '@type' => static::accountTypes()[$payment_method->account_type->value],
      '@account' => $payment_method->account_number->value,
    ];
    return $this->t('@type account @account', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['account_type'] = BundleFieldDefinition::create('list_string')
      ->setLabel(t('Account type'))
      ->setDescription(t('The account type.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', static::accountTypes());

    $fields['account_number'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Account number'))
      ->setDescription(t('The masked account number.'))
      ->setRequired(TRUE);

    return $fields;
  }

  /**
   * Provides account types.
   *
   * @return array
   *   Account types, keyed by single character identifier.
   */
  public static function accountTypes() {
    return [
      'C' => t('Checking'),
      'S' => t('Savings'),
    ];
  }

}
