<?php

namespace Drupal\bom\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\bom\BomInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the bom entity class.
 *
 * @ContentEntityType(
 *   id = "bom",
 *   label = @Translation("Bom"),
 *   handlers = {
 *     "storage" = "Drupal\bom\BomStorage",
 *     "view_builder" = "Drupal\bom\BomViewBuilder",
 *     "access" = "Drupal\bom\BomAccessControlHandler",
 *     "views_data" = "Drupal\bom\BomViewsData",
 *     "form" = {
 *       "default" = "Drupal\bom\BomForm",
 *       "delete" = "Drupal\bom\Form\BomDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   base_table = "bom",
 *   entity_keys = {
 *     "id" = "bid",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *   },
 *   admin_permission = "administer boms",
 *   links = {
 *     "add-form" = "/admin/bom/add",
 *     "canonical" = "/admin/bom/{bom}",
 *     "edit-form" = "/admin/bom/{bom}/edit",
 *     "delete-form" = "/admin/bom/{bom}/delete",
 *     "collection" = "/admin/bom",
 *   },
 * )
 */
class Bom extends ContentEntityBase implements BomInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Number'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['organization'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Organization'))
      ->setSetting('target_type', 'organization')
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

    $fields['item'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent item'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'item')
      ->addConstraint('BomAllowedItem')
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

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 0,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['attachments'] = BaseFieldDefinition::create('file')
      ->setLabel(t('Attachments'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'type' => 'file_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The timestamp that the bom was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The timestamp that the bom was last changed.'));

    return $fields;
  }

}
