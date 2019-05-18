<?php

namespace Drupal\commerce_rental_reservation\Plugin\Commerce\EntityTrait;

use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;

/**
 * Provides the "order_item_rental" trait.
 *
 * @CommerceEntityTrait(
 *   id = "order_item_rental_reservation",
 *   label = @Translation("Rental Reservation"),
 *   entity_types = {"commerce_order_item"}
 * )
 */
class OrderItemRentalReservation extends EntityTraitBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];

    $fields['instance'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Rental instance'))
      ->setDescription(t('Rental instance'))
      ->setCardinality(1)
      ->setRequired(FALSE)
      ->setSetting('target_type', 'commerce_rental_instance')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['reservation'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Rental reservation'))
      ->setDescription(t('Rental reservation'))
      ->setCardinality(1)
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'commerce_rental_reservation');


    return $fields;
  }

}
