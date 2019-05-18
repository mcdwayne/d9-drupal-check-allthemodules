<?php

namespace Drupal\record\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\record\RecordInterface;
use Drupal\Core\Entity\EditorialContentEntityBase;

/**
 * Defines the Record entity.
 *
 * @ContentEntityType(
 *   id = "record",
 *   label = @Translation("Record"),
 *   bundle_label = @Translation("Record type"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\record\RecordListBuilder",
 *     "form" = {
 *       "add" = "Drupal\record\Form\RecordForm",
 *       "default" = "Drupal\record\Form\RecordForm",
 *       "delete" = "Drupal\record\Form\RecordDeleteForm",
 *       "edit" = "Drupal\record\Form\RecordForm",
 *     },
 *     "access" = "Drupal\record\RecordAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "record",
 *   data_table = "record_field_data",
 *   revision_table = "record_revision",
 *   revision_data_table = "record_revision_field_data",
 *   admin_permission = "administer record",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "bundle" = "type",
 *     "revision" = "revision_id",
 *     "status" = "status",
 *     "published" = "status",
 *     "uid" = "uid"
 *   },
 *   links = {
 *     "add-page" = "/record/add",
 *     "add-form" = "/record/add/{record_type}",
 *     "canonical" = "/record/{record}",
 *     "edit-form" = "/record/{record}/edit",
 *     "delete-form" = "/record/{record}/delete",
 *   },
 *   bundle_entity_type = "record_type",
 *   field_ui_base_route = "entity.record_type.edit_form",
 *   default_reference_revision_settings = {
 *     "field_storage_config" = {
 *       "cardinality" = -1,
 *       "settings" = {
 *         "target_type" = "record"
 *       }
 *     },
 *     "field_config" = {
 *       "settings" = {
 *         "handler" = "default:record"
 *       }
 *     },
 *     "entity_form_display" = {
 *       "type" = "entity_reference_record"
 *     },
 *     "entity_view_display" = {
 *       "type" = "entity_reference_revisions_entity_view"
 *     }
 *   }
 * )
 */
class Record extends EditorialContentEntityBase implements RecordInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
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

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the data record'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('Record entity revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Recorded by'))
      ->setDescription(t('The username of the record editor.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setRevisionable(TRUE)
      ->setDescription(t('The time that the record was created.'))
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setRevisionable(TRUE)
      ->setDescription(t('The time that the record was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

}
