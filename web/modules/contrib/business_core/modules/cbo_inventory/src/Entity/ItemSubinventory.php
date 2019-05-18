<?php

namespace Drupal\cbo_inventory\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\cbo_inventory\ItemSubinventoryInterface;

/**
 * Defines the subinventory entity class.
 *
 * @ContentEntityType(
 *   id = "item_subinventory",
 *   label = @Translation("Item subinventory"),
 *   base_table = "item_subinventory",
 *   entity_keys = {
 *     "id" = "sid",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class ItemSubinventory extends ContentEntityBase implements ItemSubinventoryInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['subinventory'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Subinventory'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'subinventory')
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -3,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['min_qty'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Min Qty'))
      ->setDisplayOptions('view', [
        'type' => 'number_decimal',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['max_qty'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Max Qty'))
      ->setDisplayOptions('view', [
        'type' => 'number_decimal',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['uom'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Unit of Measure'))
      ->setSetting('target_type', 'uom')
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
