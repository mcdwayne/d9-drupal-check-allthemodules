<?php

namespace Drupal\baidumap_fieldtype\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface as StorageDefinition;

/**
 * Plugin implementation of the 'baidu map' field type.
 *
 * @FieldType(
 *   id = "BaidumapFieldtype",
 *   label = @Translation("Baidu Map"),
 *   description = @Translation("Stores the baidu map data."),
 *   category = @Translation("Custom"),
 *   default_widget = "BaidumapFieldtypeDefaultWidget",
 *   default_formatter = "BaidumapFieldtypeDefaultFormatter"
 * )
 */
class BaidumapFieldtype extends FieldItemBase {

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
    $properties['location'] = DataDefinition::create('string')
      ->setLabel(t('Location'));
    $properties['address'] = DataDefinition::create('string')
      ->setLabel(t('address'));
    $properties['phone'] = DataDefinition::create('string')
      ->setLabel(t('phone'));
    $properties['profile'] = DataDefinition::create('string')
      ->setLabel(t('profile'));
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
    $columns['location'] = [
      'type' => 'varchar',
      'length' => 2000,
    ];
    $columns['address'] = [
      'type' => 'varchar',
      'length' => 2000,
    ];
    $columns['phone'] = [
      'type' => 'varchar',
      'length' => 20,
    ];
    $columns['profile'] = [
      'type' => 'varchar',
      'length' => 2000,
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
   * to define when the field fype must be considered empty.
   */
  public function isEmpty() {

    $isEmpty = 
      empty($this->get('location')->getValue()) && empty($this->get('address')->getValue()) && empty($this->get('phone')->getValue()) && empty($this->get('profile')->getValue());

    return $isEmpty;
  }

} // class