<?php

namespace Drupal\commerce_pagseguro_transp\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the Authorize.net eCheck payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "pagseguro_debit",
 *   label = @Translation("Online Debit"),
 *   create_label = @Translation("Online Debit"),
 *  )
 */
class PagseguroDebit extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    return $this->t('Online Debit');
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['cpf'] = BundleFieldDefinition::create('string')
      ->setLabel(t('CPF'))
      ->setDescription(t('The CPF number'))
      ->setRequired(TRUE);

    $fields['sender_hash'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Sender hash'))
      ->setDescription(t('The sender hash code returned by Pagseguro'))
      ->setRequired(TRUE);

    $fields['bank_name'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Bank name'))
      ->setDescription(t('The name of bank of customer'))
      ->setRequired(TRUE);

    $fields['payment_link'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Payment link'))
      ->setDescription(t('The link for payment'))
      ->setRequired(TRUE);

    return $fields;
  }

}
