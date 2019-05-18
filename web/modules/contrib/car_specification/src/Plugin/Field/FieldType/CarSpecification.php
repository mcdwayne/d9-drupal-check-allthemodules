<?php

namespace Drupal\car_specification\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'car_specification' field type.
 *
 * @FieldType(
 *   id = "car_specification",
 *   label = @Translation("Car specification"),
 *   description = @Translation("List car specification"),
 *   default_widget = "car_specification_default_widget",
 *   default_formatter = "car_specification_default_formatter"
 * )
 */
class CarSpecification extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties = [];

    $properties['car_years'] = DataDefinition::create('string')
      ->setLabel(t('Car Years'));

    $properties['car_makes'] = DataDefinition::create('string')
      ->setLabel(t('Car Makes'));

    $properties['car_models'] = DataDefinition::create('string')
      ->setLabel(t('Car Models'));

    $properties['car_model_trims'] = DataDefinition::create('string')
      ->setLabel(t('Car Model Trims'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = [];
    $columns['car_years'] = [
      'type' => 'char',
    ];
    $columns['car_makes'] = [
      'type' => 'char',
    ];
    $columns['car_models'] = [
      'type' => 'char',
    ];
    $columns['car_model_trims'] = [
      'type' => 'char',
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
      empty($this->get('car_years')->getValue()) &&
      empty($this->get('car_makes')->getValue()) &&
      empty($this->get('car_models')->getValue()) &&
      empty($this->get('car_model_trims')->getValue());

    return $isEmpty;
  }

}
