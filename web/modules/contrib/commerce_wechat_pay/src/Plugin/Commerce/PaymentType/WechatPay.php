<?php

namespace Drupal\commerce_wechat_pay\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the payment type for wechat pay.
 *
 * @CommercePaymentType(
 *   id = "wechat_pay",
 *   label = @Translation("Wechat Pay"),
 *
 * )
 */
class WechatPay extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {

    $fields = [];

    $fields['wechat_openid'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Wechat Openid'))
      ->setDescription(t('The openid of the payer.'))
      ->setRequired(FALSE);

    $fields['wechat_sub_openid'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Wechat Sub-openid'))
      ->setDescription(t('The sub-openid of the payer.'))
      ->setRequired(FALSE);

    return $fields;
  }

}
