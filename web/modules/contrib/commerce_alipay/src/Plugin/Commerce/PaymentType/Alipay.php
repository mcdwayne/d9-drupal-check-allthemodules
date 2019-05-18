<?php

namespace Drupal\commerce_alipay\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the payment type for alipay pay.
 *
 * @CommercePaymentType(
 *   id = "alipay",
 *   label = @Translation("Alipay"),
 *
 * )
 */
class Alipay extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {

    $fields = [];

    $fields['alipay_buyer_user_id'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Alipay Buyer User Id'))
      ->setDescription(t('The buyer-user-id of the payer.'))
      ->setRequired(FALSE);

    return $fields;
  }

}
