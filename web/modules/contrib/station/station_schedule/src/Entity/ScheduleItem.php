<?php

/**
 * @file
 * Contains \Drupal\station_schedule\Entity\ScheduleItem.
 */

namespace Drupal\station_schedule\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\station_schedule\ScheduleItemInterface;

/**
 * @todo.
 *
 * @ContentEntityType(
 *   id = "station_schedule_item",
 *   label = @Translation("Schedule item"),
 *   base_table = "station_schedule_item",
 *   data_table = "station_schedule_item_field_data",
 *   admin_permission = "administer station schedule",
 *   handlers = {
 *     "route_provider" = {
 *       "html" = "\Drupal\station_schedule\Entity\ScheduleItemRouteProvider",
 *     },
 *     "view_builder" = "\Drupal\station_schedule\Entity\ScheduleItemViewBuilder",
 *     "form" = {
 *       "add" = "\Drupal\station_schedule\Entity\Form\ScheduleItemAddForm",
 *       "edit" = "\Drupal\station_schedule\Entity\Form\ScheduleItemEditForm",
 *       "delete" = "\Drupal\station_schedule\Entity\Form\ScheduleItemDeleteForm",
 *     },
 *   },
 *   links = {
 *     "add-form" = "/station/schedule/{station_schedule}/schedule/add/{start}/{finish}",
 *     "edit-form" = "/station/schedule/{station_schedule}/schedule/{station_schedule_item}/edit",
 *     "delete-form" = "/station/schedule/{station_schedule}/schedule/{station_schedule_item}/delete",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *   },
 * )
 */
class ScheduleItem extends ContentEntityBase implements ScheduleItemInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getProgram()->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getStart() {
    return $this->get('start')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getFinish() {
    return $this->get('finish')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchedule() {
    return $this->get('schedule')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getProgram() {
    return $this->get('program')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getDjs() {
    $program = $this->getProgram();
    $djs = [];
    if ($program instanceof FieldableEntityInterface && $program->hasField('station_program_djs')) {
      foreach ($program->get('station_program_djs') as $dj_item) {
        $djs[] = $dj_item->entity;
      }
    }
    return $djs;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Station schedule ID'))
      ->setDescription(t('The station schedule ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The station schedule UUID.'))
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The username of the content author.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['schedule'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('The Schedule ID'))
      ->setDescription(t('The ID of the schedule.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'station_schedule')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['program'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Program'))
      ->setDescription(t('The program being scheduled.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'node')
      ->setSetting('handler_settings', ['target_bundles' => ['station_program' => 'station_program']])
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['start'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Start'))
      ->setDescription(t('Starting minute from Sunday midnight.'))
      ->setSetting('unsigned', TRUE)
      ->setDefaultValue(0)
      ->setSetting('roll_midnight_back', FALSE)
      ->setDisplayOptions('form', [
        'type' => 'station_schedule_item_range',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['finish'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Finish'))
      ->setDescription(t('Finishing minute from Sunday midnight.'))
      ->setSetting('unsigned', TRUE)
      ->setDefaultValue(0)
      ->setSetting('roll_midnight_back', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'station_schedule_item_range',
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $parameters = parent::urlRouteParameters($rel);
    $parameters['station_schedule'] = $this->getSchedule()->id();
    return $parameters;
  }

}
