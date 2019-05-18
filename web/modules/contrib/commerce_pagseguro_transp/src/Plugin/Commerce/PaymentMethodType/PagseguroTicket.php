<?php

namespace Drupal\commerce_pagseguro_transp\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the Authorize.net eCheck payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "pagseguro_ticket",
 *   label = @Translation("Bank Ticket"),
 *   create_label = @Translation("Bank Ticket"),
 * )
 */
class PagseguroTicket extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    return $this->t('Bank Ticket');
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

    $fields['payment_link'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Payment link'))
      ->setDescription(t('The link for payment'))
      ->setRequired(TRUE);

    return $fields;
  }

}
