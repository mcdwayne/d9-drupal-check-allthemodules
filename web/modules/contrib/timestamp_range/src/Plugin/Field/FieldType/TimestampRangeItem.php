<?php

namespace Drupal\timestamp_range\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'timestamp range' field type.
 *
 * @FieldType(
 *   id = "timestamp_range",
 *   label = @Translation("Timestamp range"),
 *   description = @Translation("Create and store timestamp ranges."),
 *   default_widget = "datetime_timestamp_range",
 *   default_formatter = "timestamp_range"
 * )
 */
class TimestampRangeItem extends TimestampItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('timestamp')
      ->setLabel(t('Start timestamp value'))
      ->setRequired(TRUE);

    $properties['end_value'] = DataDefinition::create('timestamp')
      ->setLabel(t('End timestamp value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'int',
        ],
        'end_value' => [
          'type' => 'int',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // Pick a random timestamp range in the past year.
    $timestamp_start = \Drupal::time()->getRequestTime() - mt_rand(0, 86400 * 365);
    $values['value'] = $timestamp_start;
    $values['end_value'] = $timestamp_start + 24*60*60;
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $start_value = $this->get('value')->getValue();
    $end_value = $this->get('end_value')->getValue();
    return ($start_value === NULL || $start_value === '') && ($end_value === NULL || $end_value === '');
  }

}
