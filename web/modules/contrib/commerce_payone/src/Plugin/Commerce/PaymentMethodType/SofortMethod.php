<?php

namespace Drupal\commerce_payone\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the payment method type for Payone Sepa payments.
 *
 * @CommercePaymentMethodType(
 *   id = "commerce_payone_sofort",
 *   label = @Translation("Payone Sofort banking"),
 *   create_label = @Translation("Sofort banking"),
 * )
 */
class SofortMethod extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    return $this->t('Sofort banking');
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    /*
    $fields['iban'] = BundleFieldDefinition::create('string')
      ->setLabel(t('IBAN'))
      ->setDescription(t('IBAN'))
      ->setRequired(TRUE);

    $fields['bic'] = BundleFieldDefinition::create('string')
      ->setLabel(t('BIC'))
      ->setDescription(t('BIC'))
      ->setRequired(TRUE);
    */

    return $fields;
  }

}
