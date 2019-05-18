<?php

namespace Drupal\commerce_rental_reservation\Plugin\Commerce\EntityTrait;

use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;

/**
 * Provides the "purchasable_entity_rental_reservation" trait.
 *
 * @CommerceEntityTrait(
 *   id = "purchasable_entity_rental_reservation",
 *   label = @Translation("Reservation"),
 *   entity_types = {"commerce_product_variation"}
 * )
 */
class PurchasableEntityRentalReservation extends EntityTraitBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];

    $fields['instances'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Rental Instances'))
      ->setDescription(t('Rental Instances'))
      ->setCardinality(-1)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_rental_instance')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'inline_entity_form_complex',
        'settings' => array(
          'allow_existing' => TRUE,
          'match_operator' => 'CONTAINS',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
