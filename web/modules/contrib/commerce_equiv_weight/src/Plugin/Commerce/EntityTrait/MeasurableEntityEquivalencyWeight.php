<?php

namespace Drupal\commerce_equiv_weight\Plugin\Commerce\EntityTrait;

use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;
use Drupal\entity\BundleFieldDefinition;
use Drupal\physical\MeasurementType;

/**
 * Provides the "measurable_entity_equiv_weight" trait.
 *
 * @CommerceEntityTrait(
 *   id = "measurable_entity_equiv_weight",
 *   label = @Translation("Has equivalency weight"),
 *   entity_types = {
 *     "commerce_order",
 *     "commerce_order_item",
 *     "commerce_product_variation"
 *   }
 * )
 */
class MeasurableEntityEquivalencyWeight extends EntityTraitBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields[COMMERCE_EQUIV_WEIGHT_FIELD_EQUIVALENCY_WEIGHT] = BundleFieldDefinition::create('physical_measurement')
      ->setLabel('Equivalency Weight')
      ->setRequired(TRUE)
      ->setSetting('measurement_type', MeasurementType::WEIGHT)
      ->setDisplayOptions('form', [
        'type' => 'physical_measurement_default',
        'weight' => 99,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'commerce_equiv_weight',
        'weight' => 0,
      ]);

    return $fields;
  }

}
