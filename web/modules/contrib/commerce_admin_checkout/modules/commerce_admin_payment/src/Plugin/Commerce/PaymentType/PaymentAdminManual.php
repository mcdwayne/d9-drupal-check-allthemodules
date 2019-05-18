<?php

namespace Drupal\commerce_admin_payment\Plugin\Commerce\PaymentType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;

/**
 * Provides the manual payment type.
 *
 * @CommercePaymentType(
 *   id = "payment_admin_manual",
 *   label = @Translation("Admin Manual"),
 *   workflow = "payment_manual",
 * )
 */
class PaymentAdminManual extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    
    $fields['description'] = BundleFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Comments about this payment entered by the administrator.'));

    return $fields;
  }

}
