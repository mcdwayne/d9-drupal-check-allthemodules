<?php

namespace Drupal\commerce_rental\Plugin\Commerce\EntityTrait;

use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;

/**
 * Provides the "order_item_rental" trait.
 *
 * @CommerceEntityTrait(
 *   id = "order_item_rental",
 *   label = @Translation("Rental"),
 *   entity_types = {"commerce_order_item"}
 * )
 */
class OrderItemRental extends EntityTraitBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['rental_quantity'] = BundleFieldDefinition::create('commerce_rental_quantity')
      ->setLabel(t('Rental Quantity'))
      ->setDescription(t('Rental Quantity'))
      ->setCardinality(-1)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'rental_quantity_default',
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
