<?php

namespace Drupal\job\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\job\JobInterface;

/**
 * Defines the job entity class.
 *
 * @ContentEntityType(
 *   id = "job",
 *   label = @Translation("Job"),
 *   handlers = {
 *     "storage" = "Drupal\job\JobStorage",
 *     "access" = "Drupal\job\JobAccessControlHandler",
 *     "views_data" = "Drupal\job\JobViewsData",
 *     "form" = {
 *       "default" = "Drupal\job\JobForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   base_table = "job",
 *   entity_keys = {
 *     "id" = "jid",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   admin_permission = "administer jobs",
 *   links = {
 *     "add-form" = "/admin/job/add",
 *     "canonical" = "/admin/job/{job}",
 *     "edit-form" = "/admin/job/{job}/edit",
 *     "delete-form" = "/admin/job/{job}/delete",
 *     "collection" = "/admin/job",
 *   }
 * )
 */
class Job extends ContentEntityBase implements JobInterface {

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

    $fields['requirements'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Requirements'))
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
      ->setDescription(t('The timestamp that the job was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The timestamp that the job was last changed.'));

    return $fields;
  }

}
