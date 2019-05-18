<?php

namespace Drupal\datetime_range_timezone\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;

/**
 * Plugin implementation of the 'daterange_timezone' field type.
 *
 * @FieldType(
 *   id = "daterange_timezone",
 *   label = @Translation("Date range (with timezone)"),
 *   description = @Translation("Create and store date ranges."),
 *   default_widget = "daterange_timezone",
 *   default_formatter = "daterange_timezone",
 *   list_class = "\Drupal\datetime_range\Plugin\Field\FieldType\DateRangeFieldItemList"
 * )
 */
class DateRangeTimezone extends DateRangeItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['timezone'] = DataDefinition::create('string')
      ->setLabel(t('Timezone'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['timezone'] = [
      'type' => 'varchar',
      'length' => 255,
      'description' => 'The timezone',
    ];

    return $schema;
  }

}
