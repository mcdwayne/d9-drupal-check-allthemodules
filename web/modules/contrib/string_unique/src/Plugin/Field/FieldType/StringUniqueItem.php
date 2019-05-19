<?php

namespace Drupal\string_unique\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;

/**
 * Defines the 'string_unique' entity field type.
 *
 * @FieldType(
 *   id = "string_unique",
 *   label = @Translation("Text Unique (plain)"),
 *   description = @Translation("A field containing a plain unique string value."),
 *   category = @Translation("Text"),
 *   default_widget = "string_textfield",
 *   default_formatter = "string"
 * )
 */
class StringUniqueItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['unique keys']['value'] = ['value'];

    return $schema;
  }

}
