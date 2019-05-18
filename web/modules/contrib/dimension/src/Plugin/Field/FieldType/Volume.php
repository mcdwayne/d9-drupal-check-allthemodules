<?php

namespace Drupal\dimension\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\dimension\Plugin\Field\VolumeTrait;

/**
 * Plugin implementation of the 'volume_field_type' field type.
 *
 * @FieldType(
 *   id = "volume_field_type",
 *   label = @Translation("Dimension: Volume"),
 *   description = @Translation("Define length, width and height"),
 *   default_widget = "volume_field_widget",
 *   default_formatter = "volume_field_formatter"
 * )
 */
class Volume extends Dimension {

  use VolumeTrait;

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
