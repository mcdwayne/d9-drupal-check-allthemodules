<?php

namespace Drupal\commerce_pagseguro\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides a payment method for Pagseguro's Boleto feature.
 *
 * @CommercePaymentMethodType(
 *   id = "pagseguro_boleto",
 *   label = @Translation("Pagseguro Boleto"),
 *   create_label = @Translation("Pagseguro Boleto"),
 * )
 */
class PagseguroBoleto extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    return $this->t('Pagseguro Boleto');
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['cpf'] = BundleFieldDefinition::create('string')
      ->setLabel(t('CPF'))
      ->setDescription(t('The CPF number.'))
      ->setRequired(TRUE);

    $fields['sender_hash'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Sender hash'))
      ->setDescription(t('The sender hash returned by Pagseguro.'))
      ->setRequired(TRUE);

    $fields['payment_link'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Payment link'))
      ->setDescription(t('The link for the Pagseguro Boleto'))
      ->setRequired(TRUE);

    return $fields;
  }
}
