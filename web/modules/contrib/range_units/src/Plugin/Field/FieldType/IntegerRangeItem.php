<?php

namespace Drupal\range_units\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'integer_range' field type.
 *
 * @FieldType(
 *   id = "integer_range",
 *   label = @Translation("Range (integer)"),
 *   description = @Translation("This field stores an integer range in the database."),
 *   category = @Translation("Numeric range"),
 *   default_widget = "integer_range_widget",
 *   default_formatter = "integer_range_formatter"
 * )
 */
class IntegerRangeItem extends ItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return static::propertyDefinitionsByType('string');
  }

  /**
   * {@inheritdoc}
   */
  protected static function getColumnSpecification(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'type' => 'varchar',
      'length' => 12,
    );
  }

}
