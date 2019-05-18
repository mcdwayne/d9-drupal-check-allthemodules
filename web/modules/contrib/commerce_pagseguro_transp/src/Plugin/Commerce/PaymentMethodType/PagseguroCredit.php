<?php

namespace Drupal\commerce_pagseguro_transp\Plugin\Commerce\PaymentMethodType;

// Unused statments.
// Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase.
// Drupal\commerce_payment\Entity\PaymentMethodInterface.
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\CreditCard;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the Authorize.net eCheck payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "pagseguro_credit",
 *   label = @Translation("Credit Card"),
 *   create_label = @Translation("Credit Card"),
 * )
 */
class PagseguroCredit extends CreditCard {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['cpf'] = BundleFieldDefinition::create('string')
      ->setLabel(t('CPF'))
      ->setDescription(t('The CPF number of card holder'))
      ->setRequired(TRUE);

    $fields['card_holder_name'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Holder name'))
      ->setDescription(t('The name of card holder'))
      ->setRequired(TRUE);

    $fields['installment_amount'] = BundleFieldDefinition::create('decimal')
      ->setLabel(t('Installment amount'))
      ->setSetting('decimal', '10.2')
      ->setDescription(t('The installment amount'))
      ->setRequired(TRUE);

    $fields['installments_qty'] = BundleFieldDefinition::create('integer')
      ->setLabel(t('Installments quantity'))
      ->setDescription(t('The installments quantity'))
      ->setRequired(TRUE);

    $fields['sender_hash'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Sender hash'))
      ->setDescription(t('The sender hash code returned by Pagseguro'))
      ->setRequired(TRUE);

    return $fields;
  }

}
