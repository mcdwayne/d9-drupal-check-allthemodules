<?php

namespace Drupal\flexible_daterange\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;

/**
 * Plugin implementation of the 'flexible_daterange' field type.
 *
 * @FieldType(
 *   id = "flexible_daterange",
 *   label = @Translation("Flexible date range"),
 *   description = @Translation("Create and store date ranges with the option to hide time."),
 *   default_widget = "flexible_daterange_default",
 *   default_formatter = "flexible_daterange_default",
 * )
 */
class FlexibleDateRangeItem extends DateRangeItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['hide_time'] = DataDefinition::create('boolean')
      ->setLabel(t('Hide time'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['hide_time'] = [
      'description' => 'A boolean indicating whether to hide the time of the flexible_daterange field.',
      'type' => 'int',
      'default' => 0,
      'size' => 'tiny',
    ];

    return $schema;
  }

}
