<?php

namespace Drupal\commerce_payone\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the payment method type for Payone Sepa payments.
 *
 * @CommercePaymentMethodType(
 *   id = "commerce_payone_sepa",
 *   label = @Translation("Payone sepa payment"),
 *   create_label = @Translation("SEPA"),
 * )
 */
class SepaMethod extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    if ($payment_method->getRemoteId()) {
      $payment_gateway = $payment_method->getPaymentGateway()->getPlugin();
      return $this->t('SEPA mandate: @description', [
        // '@description' => $payment_gateway->getMandateDescription($payment_method),
        '@description' => $payment_method->iban->value,
      ]);
    }
    else {
      return $this->t('New SEPA mandate');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['iban'] = BundleFieldDefinition::create('string')
      ->setLabel(t('IBAN'))
      ->setDescription(t('IBAN'))
      ->setRequired(TRUE);

    $fields['bic'] = BundleFieldDefinition::create('string')
      ->setLabel(t('BIC'))
      ->setDescription(t('BIC'))
      ->setRequired(TRUE);

    return $fields;
  }

}
