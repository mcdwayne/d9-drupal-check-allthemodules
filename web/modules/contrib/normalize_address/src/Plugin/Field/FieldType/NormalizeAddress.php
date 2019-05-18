<?php

namespace Drupal\normalize_address\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface as StorageDefinition;

/**
 * Plugin implementation of the 'normalize_address' field type.
 *
 * @FieldType(
 *   id = "normalize_address",
 *   label = @Translation("Normalize Address"),
 *   description = @Translation("Stories normalized address returned by Google API."),
 *   category = @Translation("Normalize Address"),
 *   default_widget = "NormalizeAddressDefaultWidget",
 *   default_formatter = "NormalizeAddressDefaultFormatter"
 * )
 */
class NormalizeAddress extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(StorageDefinition $storage) {

    $properties = [];

    $properties['normalized_address_full'] = DataDefinition::create('string')
      ->setLabel(t('Full Address'));

    $properties['normalized_address_province'] = DataDefinition::create('string')
      ->setLabel(t('Province'));

    $properties['normalized_address_city'] = DataDefinition::create('string')
      ->setLabel(t('City'));

    $properties['normalized_address_street_address'] = DataDefinition::create('string')
      ->setLabel(t('Street address'));

    $properties['normalized_address_building_number'] = DataDefinition::create('string')
      ->setLabel(t('Building Number'));

    $properties['normalized_address_unit_number'] = DataDefinition::create('integer')
      ->setLabel(t('Unit Number'));

    $properties['normalized_address_postal_code'] = DataDefinition::create('string')
      ->setLabel(t('Postal Code'));

    $properties['normalized_address_lattitude'] = DataDefinition::create('string')
      ->setLabel(t('Lattitude'));

    $properties['normalized_address_longtitude'] = DataDefinition::create('string')
      ->setLabel(t('Longtitude'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(StorageDefinition $storage) {
    $columns = [];
    $columns['normalized_address_full'] = [
      'type' => 'char',
      'length' => 255,
    ];
    $columns['normalized_address_province'] = [
      'type' => 'char',
      'length' => 255,
    ];
    $columns['normalized_address_city'] = [
      'type' => 'char',
      'length' => 255,
    ];
    $columns['normalized_address_street_address'] = [
      'type' => 'char',
      'length' => 255,
    ];
    $columns['normalized_address_building_number'] = [
      'type' => 'char',
      'length' => 255,
    ];
    $columns['normalized_address_unit_number'] = [
      'type' => 'int',
      'length' => 11,
    ];
    $columns['normalized_address_postal_code'] = [
      'type' => 'char',
      'length' => 255,
    ];
    $columns['normalized_address_lattitude'] = [
      'type' => 'char',
      'length' => 255,
    ];
    $columns['normalized_address_longtitude'] = [
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
      empty($this->get('normalized_address_full')->getValue()) &&
      empty($this->get('normalized_address_province')->getValue()) &&
      empty($this->get('normalized_address_city')->getValue()) &&
      empty($this->get('normalized_address_street_address')->getValue()) &&
      empty($this->get('normalized_address_building_number')->getValue()) &&
      empty($this->get('normalized_address_unit_number')->getValue()) &&
      empty($this->get('normalized_address_postal_code')->getValue()) &&
      empty($this->get('normalized_address_lattitude')->getValue()) &&
      empty($this->get('normalized_address_longtitude')->getValue());

    return $isEmpty;
  }

}
