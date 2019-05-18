<?php

namespace Drupal\commerce_rental\Plugin\Commerce\EntityTrait;

use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;

/**
 * Provides the "purchasable_entity_rentable" trait.
 *
 * @CommerceEntityTrait(
 *   id = "purchasable_entity_rentable",
 *   label = @Translation("Rentable"),
 *   entity_types = {"commerce_product_variation"}
 * )
 */
class PurchasableEntityRentable extends EntityTraitBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['rental_rates'] = BundleFieldDefinition::create('commerce_rental_rate')
      ->setLabel(t('Rental Rate'))
      ->setDescription(t('Rental Rates'))
      ->setCardinality(-1)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'rental_rate_default',
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
