<?php

namespace Drupal\commerce_pagos_net\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the PayPal payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "pagos_net",
 *   label = @Translation("Pagos Net"),
 *   create_label = @Translation("Pagos Net"),
 * )
 */
class PagosNet extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    $args = [
      '@nit' => $payment_method->nit->value,
    ];
    return $this->t('Pagos Net (NIT: @nit)', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['nit'] = BundleFieldDefinition::create('string')
      ->setLabel(t('NIT'))
      ->setDescription(t('Invoice NIT.'))
      ->setRequired(TRUE);

    $fields['fiscal_name'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Fiscal name'))
      ->setDescription(t('Invoice fiscal name.'))
      ->setRequired(TRUE);

    return $fields;
  }

}