<?php

namespace Drupal\payex_commerce\Plugin\Commerce\PaymentType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentDefault;

/**
 * Provides the default payment type.
 *
 * @CommercePaymentType(
 *   id = "payex",
 *   label = @Translation("PayEx"),
 * )
 */
class PayEx extends PaymentDefault  {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['transaction_id'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Transaction ID'))
      ->setDescription(t('The ID of the transaction.'))
      ->setRequired(FALSE);

    $fields['masked_cc'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Masked CC'))
      ->setDescription(t('Masked credit card number used in the transaction.'))
      ->setRequired(FALSE);

    $fields['card_type'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Cart type'))
      ->setDescription(t('The card type used.'))
      ->setRequired(FALSE);

    $fields['payex_redirect_url'] = BundleFieldDefinition::create('uri')
      ->setLabel(t('PayEx Redirect URL'))
      ->setDescription(t('The redirect/iframe URL, where the user can complete the payment.'))
      ->setRequired(FALSE);

    return $fields;
  }

}
