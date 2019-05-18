<?php

namespace Drupal\field_entity_dependency\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface as StorageDefinition;
use Drupal\field_entity_dependency\Plugin\Field\FieldWidget\DependencyDefaultWidget;

/**
 * Plugin implementation of the 'dependency' field type.
 *
 * @FieldType(
 *   id = "Dependency",
 *   label = @Translation("Dependency"),
 *   description = @Translation("Stores an address."),
 *   category = @Translation("Reference"),
 *   default_widget = "DependencyDefaultWidget",
 *   default_formatter = "DependencyDefaultFormatter"
 * )
 */
class Dependency extends FieldItemBase {

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

    // get the current delta
    $delta = DependencyDefaultWidget::getMaxDelta();

    $properties['select_parent'] = DataDefinition::create('string')
      ->setLabel(t('Select_parent'));

    // for the multiple selects
    for ($i = 0; $i < $delta; $i++) {
      $properties['select_child_'.$i] = DataDefinition::create('string')
        ->setLabel(t('Select_child_'.$i));
    }

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


    // get the current delta
    $delta = DependencyDefaultWidget::getMaxDelta();

    $columns = [];
    $columns['select_parent'] = [
      'type' => 'char',
      'length' => 255,
    ];

    // for the multiple selects
    for ($i = 0; $i < $delta; $i++) {
      $columns['select_child_'.$i] = [
        'type' => 'char',
        'length' => 255,
      ];
    }

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
    // get the current delta
    $delta = DependencyDefaultWidget::getMaxDelta();

    $isEmpty = FALSE;
    if (empty($this->get('select_parent')->getValue())) {
      $isEmpty = TRUE;
    }
    else {
      // for the multiple selects
      for ($i = 0; $i < $delta; $i++) {
        if (empty($this->get('select_child_'.$i)->getValue())) {
          $isEmpty = TRUE;
        }
      }
    }

    return $isEmpty;
  }

}