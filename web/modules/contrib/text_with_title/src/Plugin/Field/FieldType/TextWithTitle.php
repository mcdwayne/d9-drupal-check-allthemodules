<?php

namespace Drupal\text_with_title\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface as StorageDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Plugin implementation of the 'Text Title' field type.
 *
 * @FieldType(
 *   id = "text_with_title_field",
 *   label = @Translation("Text with Title"),
 *   description = @Translation("A wysiwyg area with associated title."),
 *   category = @Translation("Custom"),
 *   default_widget = "text_with_title_widget",
 *   default_formatter = "text_with_title_formatter"
 * )
 */
class TextWithTitle extends FieldItemBase {

  /**
   * Field type properties definition.
   *
   * Inside this method we defines all the fields (properties) that our
   * custom field type will have.
   *
   * Here there is a list of allowed property types: https://goo.gl/sIBBgO
   */
  public static function propertyDefinitions(StorageDefinition $storage) {
    $properties = [];
    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Title'));
    $properties['text'] = MapDataDefinition::create()
      ->setLabel(t('Text'));
    return $properties;
  }

  /**
   * Field type schema definition.
   *
   * Inside this method we defines the database schema used to store data for
   * our field type.
   *
   * Here there is a list of allowed column types: https://goo.gl/YY3G7s
   */
  public static function schema(StorageDefinition $storage) {
    $columns = [];
    $columns['title'] = [
      'type' => 'varchar',
      'length' => 255,
    ];
    $columns['text'] = [
      'type' => 'blob',
      'size' => 'big',
      'serialize' => TRUE,
    ];
    return [
      'columns' => $columns,
      'indexes' => [],
    ];
  }

  /**
   * Define when the field type is empty.
   *
   * This method is important and used internally by Drupal. Take a moment
   * to define when the field type must be considered empty.
   */
  public function isEmpty() {
    // @todo check text value from array
    $isEmpty = empty($this->get('title')->getValue());
    return $isEmpty;
  }

}
