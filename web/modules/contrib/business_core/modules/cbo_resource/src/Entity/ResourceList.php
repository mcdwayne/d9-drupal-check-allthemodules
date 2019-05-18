<?php

namespace Drupal\cbo_resource\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\cbo_resource\ResourceListInterface;

/**
 * Defines the resource_list entity class.
 *
 * @ContentEntityType(
 *   id = "resource_list",
 *   label = @Translation("Resource List"),
 *   handlers = {
 *     "storage" = "Drupal\cbo_resource\ResourceListStorage",
 *     "access" = "Drupal\cbo_resource\ResourceListAccessControlHandler",
 *     "views_data" = "Drupal\cbo_resource\ResourceListViewsData",
 *     "form" = {
 *       "default" = "Drupal\cbo_resource\ResourceListForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   base_table = "resource_list",
 *   entity_keys = {
 *     "id" = "rid",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   admin_permission = "administer resource lists",
 *   links = {
 *     "add-form" = "/admin/resource/list/add",
 *     "canonical" = "/admin/resource/list/{resource_list}",
 *     "edit-form" = "/admin/resource/list/{resource_list}/edit",
 *     "delete-form" = "/admin/resource/list/{resource_list}/delete",
 *     "collection" = "/admin/resource/list",
 *   }
 * )
 */
class ResourceList extends ContentEntityBase implements ResourceListInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('Job title'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
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

    $fields['resources'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Resources'))
      ->setCardinality(FieldStorageConfigInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'resource')
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_complex',
        'weight' => 0,
        'settings' => [
          'form_mode' => 'default',
          'allow_new' => TRUE,
          'allow_existing' => TRUE,
          'match_operator' => 'CONTAINS',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The timestamp that the resource_list was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The timestamp that the resource_list was last changed.'));

    return $fields;
  }

}
