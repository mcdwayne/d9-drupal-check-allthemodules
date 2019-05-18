<?php

namespace Drupal\availability_timing\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'tablefield' field type.
 *
 * @FieldType (
 *   id = "availability_timing",
 *   label = @Translation("Availability Timing"),
 *   description = @Translation("Store a complex availability timing on database"),
 *   default_widget = "availability_timing_default",
 *   default_formatter = "availability_timing_default"
 * )
 */
class AvailabilityTimingItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'start_period_month' => [
          'type' => 'int',
          'size' => 'normal',
          'not null' => FALSE,
        ],
        'start_period_day' => [
          'type' => 'int',
          'size' => 'normal',
          'not null' => FALSE,
        ],
        'end_period_month' => [
          'type' => 'int',
          'size' => 'normal',
          'not null' => FALSE,
        ],
        'end_period_day' => [
          'type' => 'int',
          'size' => 'normal',
          'not null' => FALSE,
        ],
        'sun' => [
          'type' => 'int',
          'not null' => FALSE,
          'default' => 0,
        ],
        'mon' => [
          'type' => 'int',
          'not null' => FALSE,
          'default' => 0,
        ],
        'tue' => [
          'type' => 'int',
          'not null' => FALSE,
          'default' => 0,
        ],
        'wed' => [
          'type' => 'int',
          'not null' => FALSE,
          'default' => 0,
        ],
        'thu' => [
          'type' => 'int',
          'not null' => FALSE,
          'default' => 0,
        ],
        'fri' => [
          'type' => 'int',
          'not null' => FALSE,
          'default' => 0,
        ],
        'sat' => [
          'type' => 'int',
          'not null' => FALSE,
          'default' => 0,
        ],
        'timing' => [
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['start_period_month'] = DataDefinition::create('integer')
      ->setLabel(t('Start period month'));
    $properties['start_period_day'] = DataDefinition::create('integer')
      ->setLabel(t('Start period day'));
    $properties['end_period_month'] = DataDefinition::create('integer')
      ->setLabel(t('End period month'));
    $properties['end_period_day'] = DataDefinition::create('integer')
      ->setLabel(t('End period day'));
    $properties['sun'] = DataDefinition::create('integer')
      ->setLabel(t('Sunday'));
    $properties['mon'] = DataDefinition::create('integer')
      ->setLabel(t('Monday'));
    $properties['tue'] = DataDefinition::create('integer')
      ->setLabel(t('Tuesday'));
    $properties['wed'] = DataDefinition::create('integer')
      ->setLabel(t('Wednesday'));
    $properties['thu'] = DataDefinition::create('integer')
      ->setLabel(t('Thursday'));
    $properties['fri'] = DataDefinition::create('integer')
      ->setLabel(t('Friday'));
    $properties['sat'] = DataDefinition::create('integer')
      ->setLabel(t('Saturday'));
    $properties['timing'] = MapDataDefinition::create()->setLabel(t('Availability timing'));
    $properties['availability_timing'] = DataDefinition::create('any')
      ->setLabel(t('Computed availability timing'))
      ->setDescription(t('The computed availability timing object.'))
      ->setComputed(TRUE)
      ->setClass('\\Drupal\\availability_timing\\AvailabilityTimingComputed')
      ->setSetting('timing source', 'timing');
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $start_month = $this->get('start_period_month')->getValue();
    $start_day = $this->get('start_period_day')->getValue();
    $end_month = $this->get('end_period_month')->getValue();
    $end_day = $this->get('end_period_day')->getValue();
    if (empty($start_month) && empty($start_day) && empty($end_month) && empty($end_day)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'minute_granularity' => 30,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['minute_granularity'] = [
      '#type' => 'select',
      '#title' => t('Minute granularity'),
      '#default_value' => $this->getSetting('minute_granularity'),
      '#options' => [
        1 => 1,
        5 => 5,
        10 => 10,
        20 => 20,
        30 => 30,
        60 => 60,
      ],
    ];
    return $element;
  }

}
