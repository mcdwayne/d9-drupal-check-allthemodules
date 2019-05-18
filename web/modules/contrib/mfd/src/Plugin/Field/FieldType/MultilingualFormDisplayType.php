<?php

namespace Drupal\mfd\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'multilingual_form_display' field type.
 *
 * @FieldType(
 *   id = "multilingual_form_display",
 *   label = @Translation("Multilingual Form Display"),
 *   description = @Translation("This field exposed all other fields of an entity which set translatable."),
 *   category = @Translation("Multilingual"),
 *   default_widget = "multilingual_form_display_widget",
 *   default_formatter = "multilingual_form_display_formatter",
 *   cardinality = 1,
 * )
 */
class MultilingualFormDisplayType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('boolean')
      ->setRequired(TRUE);
//      ->setComputed(TRUE)
//      ->setClass('\Drupal\mfd\FieldNullCompute');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $columns = [];
    $columns['value'] = [
      'type' => 'int',
      'length' => 2,
    ];

    return [
      'columns' => $columns,
      'indexes' => [],
    ];

  }
}
