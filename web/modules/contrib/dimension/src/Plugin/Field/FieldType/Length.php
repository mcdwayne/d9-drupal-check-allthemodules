<?php

namespace Drupal\dimension\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\dimension\Plugin\Field\LengthTrait;

/**
 * Plugin implementation of the 'length_field_type' field type.
 *
 * @FieldType(
 *   id = "length_field_type",
 *   label = @Translation("Dimension: Length"),
 *   description = @Translation("Define length"),
 *   default_widget = "length_field_widget",
 *   default_formatter = "length_field_formatter"
 * )
 */
class Length extends Dimension {

  use LengthTrait;

  /**
   * @inheritdoc
   */
  public static function defaultStorageSettings() {
    return self::_defaultStorageSettings(self::fields());
  }

  /**
   * @inheritdoc
   */
  public static function defaultFieldSettings() {
    return self::_defaultFieldSettings(self::fields());
  }

  /**
   * @inheritdoc
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return self::_propertyDefinitions($field_definition, self::fields());
  }

  /**
   * @inheritdoc
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return self::_schema($field_definition, self::fields());
  }

}
