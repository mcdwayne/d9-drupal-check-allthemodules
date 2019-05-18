<?php

namespace Drupal\commerce_braintree_marketplace\Plugin\Commerce\PaymentType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;

/**
 * Provides the submerchant/marketplace payment type.
 *
 * @CommercePaymentType(
 *   id = "payment_braintree_submerchant",
 *   label = @Translation("Submerchant"),
 * )
 */
class SubMerchantPayment extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [
      'seller_profile' => BundleFieldDefinition::create('entity_reference')
        ->setLabel('Seller Profile')
        ->setDescription('Sub-Merchant seller profile')
        ->setCardinality(1)
        ->setSetting('target_type', 'profile')
        ->setSetting('handler', 'default'),
      'escrow_status' => BundleFieldDefinition::create('string')
        ->setLabel('Escrow status')
        ->setDescription('Payment escrow status')
        ->setCardinality(1),
      'service_fee' => BundleFieldDefinition::create('commerce_price')
        ->setLabel(t('Service fee amount'))
        ->setDescription(t('Service Fee'))
        ->setCardinality(1)
        ->setDisplayConfigurable('view', TRUE),
    ];
  }

}
