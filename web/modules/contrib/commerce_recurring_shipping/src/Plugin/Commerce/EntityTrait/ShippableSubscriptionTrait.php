<?php

namespace Drupal\commerce_recurring_shipping\Plugin\Commerce\EntityTrait;

use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Provides a trait to enable shipping of subscriptions.
 *
 * @CommerceEntityTrait(
 *   id = "shippable_subscription",
 *   label = @Translation("Allow shipping"),
 *   entity_types = {"commerce_subscription"}
 * )
 */
class ShippableSubscriptionTrait extends EntityTraitBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['shipping_profile'] = BundleFieldDefinition::create('entity_reference_revisions')
      ->setLabel(t('Shipping information'))
      ->setSetting('target_type', 'profile')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['customer']])
      ->setDisplayOptions('form', [
        'type' => 'commerce_shipping_profile',
        'weight' => 2,
        'settings' => [],
      ])
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_revisions_entity_view',
        'weight' => 10,
        'label' => 'above',
        'settings' => [
          'view_mode' => 'default',
        ]
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['shipping_method'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Shipping method'))
      ->setDescription(t('The shipping method'))
      ->setSetting('target_type', 'commerce_shipping_method')
      ->setDisplayOptions('form', [
        'type' => 'commerce_shipping_rate',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'commerce_shipping_method',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
