<?php

namespace Drupal\grnhse\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\grnhse\JobInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the GreenhouseJob entity.
 *
 * @ingroup grnhse
 *
 * @ContentEntityType(
 *   id = "grnhse_job",
 *   label = @Translation("Greenhouse Job entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData"
 *   },
 *   base_table = "grnhse_job",
 *   admin_permission = "administer greenhouse data",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   field_ui_base_route = "grnhse.course_settings",
 * )
 *
 */
class Job extends ContentEntityBase implements JobInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
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
   *
   * Field properties for the grnhse_job entity
   *
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Job entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Job entity.'))
      ->setReadOnly(TRUE);

    // Name field for the job.
    // feed: title
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The job title.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      // Set no default value.
      ->setDefaultValue(NULL);

    // Department field for the job.
    // feed: departments[]->name
    $fields['department'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Department'))
      ->setDescription(t('The job department.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      // Set no default value.
      ->setDefaultValue(NULL);

    // Greenhouse ID.
    // feed: id
    $fields['ext_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Greenhouse ID'))
      ->setDescription(t('The API ID of the Job entity.'))
      ->setSettings([
        'max_length' => 12,
        'text_processing' => 0,
      ])
      // Set no default value.
      ->setDefaultValue(NULL);

    // Internal ID.
    // feed: internal_job_id
    $fields['internal_job_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Internal ID'))
      ->setDescription(t('The Internal ID of the Job entity.'))
      ->setSettings([
        'max_length' => 12,
        'text_processing' => 0,
      ])
      // Set no default value.
      ->setDefaultValue(NULL);

    // URL field.
    // feed: absolute_url
    $fields['absolute_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('URL'))
      ->setDescription(t('The URL to the Greenhouse listing.'))
      // Set no default value.
      ->setDefaultValue(NULL);

    // Location field.
    /*
      "location":{
        "name":"NYC"
      }
    */
    $fields['location'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Location'))
      ->setDescription(t('Location(s) for this Job.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      // Set no default value.
      ->setDefaultValue(NULL);


    // Updated Date field.
    // feed: updated_at
    $fields['updated_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Updated At'))
      ->setDescription(t('The date posting was updated.'))
      ->setSettings([
        'datetime_type' => 'datetime'
      ])
      // Set no default value.
      ->setDefaultValue(NULL);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
