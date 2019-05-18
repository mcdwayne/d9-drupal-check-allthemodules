<?php

namespace Drupal\bom\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\bom\ComponentInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the BOM component entity class.
 *
 * @ContentEntityType(
 *   id = "bom_component",
 *   label = @Translation("BOM component"),
 *   handlers = {
 *     "access" = "Drupal\bom\ComponentAccessControlHandler",
 *     "views_data" = "Drupal\bom\ComponentViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "bom_component",
 *   entity_keys = {
 *     "id" = "cid",
 *     "uuid" = "uuid"
 *   },
 *   admin_permission = "administer boms",
 *   links = {
 *     "add-form" = "/admin/bom/add",
 *     "edit-form" = "/admin/bom/{bom}/edit",
 *     "delete-form" = "/admin/bom/{bom}/delete",
 *   },
 * )
 */
class Component extends ContentEntityBase implements ComponentInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['bom'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('BOM'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'organization');

    $fields['item'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Item'))
      ->setSetting('target_type', 'item')
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

    $fields['substitute_components'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Substitute components'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'item')
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

    $fields['quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Quantity'))
      ->setDisplayOptions('view', [
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The timestamp that the component was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The timestamp that the component was last changed.'));

    return $fields;
  }

}
