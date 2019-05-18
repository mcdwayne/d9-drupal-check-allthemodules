<?php

namespace Drupal\commerce_pagseguro\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\CreditCard;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides a payment method for PagSeguro's Checkout Transparente.
 *
 * @CommercePaymentMethodType(
 *   id = "pagseguro_credit_card",
 *   label = @Translation("Credit Card via Checkout Transparente"),
 *   create_label = @Translation("Credit Card via Checkout Transparente"),
 * )
 */
class PagseguroCreditCard extends CreditCard {

   /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    return $this->t('Credit Card via Checkout Transparente');
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['cpf'] = BundleFieldDefinition::create('string')
      ->setLabel(t('CPF'))
      ->setDescription(t('The CPF number of card holder.'))
      ->setRequired(TRUE);

    $fields['card_holder_name'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Holder name'))
      ->setDescription(t('The full name of the card holder.'))
      ->setRequired(TRUE);

    $fields['installment_amount'] = BundleFieldDefinition::create('decimal')
      ->setLabel(t('Installment amount'))
      ->setSetting('decimal', '10.2')
      ->setDescription(t('The installment amount.'))
      ->setRequired(TRUE);

    $fields['installments_qty'] = BundleFieldDefinition::create('integer')
      ->setLabel(t('Installments quantity'))
      ->setDescription(t('The number of installments.'))
      ->setRequired(TRUE);

    $fields['sender_hash'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Sender hash'))
      ->setDescription(t('The sender hash code returned by Pagseguro.'))
      ->setRequired(TRUE);

    return $fields;
  }
}
