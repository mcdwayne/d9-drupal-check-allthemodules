<?php

namespace Drupal\commerce_cib\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the default payment type.
 *
 * @CommercePaymentType(
 *   id = "payment_cib",
 *   label = @Translation("CIB"),
 *   workflow = "payment_manual",
 * )
 */
class PaymentCib extends PaymentTypeBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['payment_cib_eki_user'] = BundleFieldDefinition::create('string')
      ->setLabel($this->t('EKI user'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment_cib_msg'] = BundleFieldDefinition::create('string')
      ->setLabel('msgt')
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment_cib_anum'] = BundleFieldDefinition::create('string')
      ->setLabel('Anum')
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment_cib_history'] = BundleFieldDefinition::create('string')
      ->setLabel($this->t('History'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment_cib_rc'] = BundleFieldDefinition::create('string')
      ->setLabel($this->t('RC'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment_cib_rt'] = BundleFieldDefinition::create('string')
      ->setLabel($this->t('RT'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment_cib_start'] = BundleFieldDefinition::create('timestamp')
      ->setLabel(t('Transaction start'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment_cib_end'] = BundleFieldDefinition::create('timestamp')
      ->setLabel(t('Transaction end'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
