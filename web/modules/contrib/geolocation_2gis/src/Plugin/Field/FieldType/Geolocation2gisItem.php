<?php

namespace Drupal\geolocation_2gis\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'geolocation2gis' field type.
 *
 * @FieldType(
 *   id = "geolocation2gis",
 *   label = @Translation("Geolocation 2GIS"),
 *   description = @Translation("This field stores location data (lat, lng)."),
 *   default_widget = "geolocation2gis_latlng",
 *   default_formatter = "geolocation2gis_latlng"
 * )
 */
class Geolocation2gisItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'lat' => [
          'description' => 'Stores the latitude value',
          'type' => 'float',
          'size' => 'big',
          'not null' => TRUE,
        ],
        'lng' => [
          'description' => 'Stores the longitude value',
          'type' => 'float',
          'size' => 'big',
          'not null' => TRUE,
        ],
      ],
      'indexes' => [
        'lat' => ['lat'],
        'lng' => ['lng'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['lat'] = DataDefinition::create('float')
      ->setLabel(t('Latitude'));

    $properties['lng'] = DataDefinition::create('float')
      ->setLabel(t('Longitude'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['lat'] = rand(-90, 90) - rand(0, 999999) / 1000000;
    $values['lng'] = rand(-180, 180) - rand(0, 999999) / 1000000;
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $lat = $this->get('lat')->getValue();
    $lng = $this->get('lng')->getValue();
    return $lat === NULL || $lat === '' || $lng === NULL || $lng === '';
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $this->get('lat')->setValue(trim($this->get('lat')->getValue()));
    $this->get('lng')->setValue(trim($this->get('lng')->getValue()));
  }

}
