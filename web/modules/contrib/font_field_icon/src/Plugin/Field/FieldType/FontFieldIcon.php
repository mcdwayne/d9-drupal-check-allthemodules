<?php

namespace Drupal\font_field_icon\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface as StorageDefinition;

/**
 * Plugin implementation of the 'font_field_icon' field type.
 *
 * @FieldType(
 *   id = "font_field_icon",
 *   label = @Translation("Icon field"),
 *   description = @Translation("Stores an Icon field."),
 *   category = @Translation("Icon field"),
 *   default_widget = "FontFieldIconDefaultWidget",
 *   default_formatter = "FontFieldIconDefaultFormatter"
 * )
 */
class FontFieldIcon extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(StorageDefinition $storage) {

    $properties = [];

    $properties['font_field_icon'] = DataDefinition::create('string')
      ->setLabel(t('Icon for field'));

    $properties['font_field_icon_link'] = DataDefinition::create('string')
      ->setLabel(t('Text in field'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(StorageDefinition $storage) {
    $columns = [];
    $columns['font_field_icon'] = [
      'type' => 'char',
      'length' => 255,
    ];
    $columns['font_field_icon_link'] = [
      'type' => 'char',
      'length' => 255,
    ];

    return [
      'columns' => $columns,
      'indexes' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $isEmpty =
      empty($this->get('font_field_icon')->getValue()) &&
      empty($this->get('font_field_icon_link')->getValue());

    return $isEmpty;
  }

}
