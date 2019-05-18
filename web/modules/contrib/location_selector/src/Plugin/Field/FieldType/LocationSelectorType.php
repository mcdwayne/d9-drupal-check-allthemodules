<?php

namespace Drupal\location_selector\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'location_selector_type' field type.
 *
 * @FieldType(
 *   id = "location_selector_type",
 *   label = @Translation("Location Selector"),
 *   category = @Translation("Location Selector"),
 *   description = @Translation("My Field Type"),
 *   default_widget = "location_selector_widget",
 *   default_formatter = "location_selector_formatter"
 * )
 */
class LocationSelectorType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'json',
          'pgsql_type' => 'json',
          'mysql_type' => 'json',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('JSON value'));

    return $properties;
  }

}
