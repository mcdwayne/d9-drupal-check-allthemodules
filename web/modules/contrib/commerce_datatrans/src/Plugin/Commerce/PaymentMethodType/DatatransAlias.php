<?php

namespace Drupal\commerce_datatrans\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the Datatrans alias payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "datatrans_alias",
 *   label = @Translation("Datatrans alias"),
 *   create_label = @Translation("Datatrans alias payment"),
 * )
 */
class DatatransAlias extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    return $this->t('Datatrans alias');
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['pmethod'] = BundleFieldDefinition::create('string')
      ->setLabel($this->t('Payment method'))
      ->setDescription($this->t('The used payment method (VIS, AMX,...)'));

    $fields['masked_cc'] = BundleFieldDefinition::create('string')
      ->setLabel($this->t('Datatrans masked CC'))
      ->setDescription($this->t('The masced cc number'));

    $fields['expm'] = BundleFieldDefinition::create('integer')
      ->setLabel($this->t('Expiry month'))
      ->setDescription($this->t('Expiry month of the card'));

    $fields['expy'] = BundleFieldDefinition::create('integer')
      ->setLabel($this->t('Expiry year'))
      ->setDescription($this->t('Expiry year of the card'));

    return $fields;
  }

}
