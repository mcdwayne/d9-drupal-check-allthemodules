<?php

namespace Drupal\international_phone\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface as StorageDefinition;

/**
 * Plugin implementation of the 'Internation Phone' field type.
 *
 * @FieldType(
 *   id = "international_phone",
 *   label = @Translation("International Phone"),
 *   description = @Translation("Stores an International phone."),
 *   category = @Translation("Custom"),
 *   default_widget = "InternationalPhoneDefaultWidget",
 *   default_formatter = "InternationalPhoneDefaultFormatter"
 * )
 */
class InternationalPhone extends FieldItemBase {

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

    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Internation Phone'));

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

    return [
      'columns' => [
        'value' => [
          'type' => 'char',
          'length' => 255,
          'not null' => FALSE,
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

  /**
   * Define when the field type is empty.
   *
   * This method is important and used internally by Drupal. Take a moment
   * to define when the field fype must be considered empty.
   */
  public function isEmpty() {

    $isEmpty = empty($this->get('value')->getValue());

    return $isEmpty;
  }

}
