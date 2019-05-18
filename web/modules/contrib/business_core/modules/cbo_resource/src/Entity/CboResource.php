<?php

namespace Drupal\cbo_resource\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\cbo_resource\ResourceInterface;

/**
 * Defines the resource entity class.
 *
 * @ContentEntityType(
 *   id = "resource",
 *   label = @Translation("Resource"),
 *   bundle_label = @Translation("Resource type"),
 *   handlers = {
 *     "storage" = "Drupal\cbo_resource\ResourceStorage",
 *     "access" = "Drupal\cbo_resource\ResourceAccessControlHandler",
 *     "views_data" = "Drupal\cbo_resource\ResourceViewsData",
 *     "form" = {
 *       "default" = "Drupal\cbo_resource\ResourceForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   base_table = "resource",
 *   entity_keys = {
 *     "id" = "rid",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *   },
 *   bundle_entity_type = "resource_type",
 *   field_ui_base_route = "entity.resource_type.edit_form",
 *   admin_permission = "administer resources",
 *   links = {
 *     "add-page" = "/admin/resource/add",
 *     "add-form" = "/admin/resource/add/{resource_type}",
 *     "canonical" = "/admin/resource/{resource}",
 *     "edit-form" = "/admin/resource/{resource}/edit",
 *     "delete-form" = "/admin/resource/{resource}/delete",
 *     "collection" = "/admin/resource",
 *   }
 * )
 */
class CboResource extends ContentEntityBase implements ResourceInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
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
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
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

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 10,
        'settings' => [
          'rows' => 4,
        ],
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
