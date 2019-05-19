<?php

namespace Drupal\yamaps\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'Yandex Map' field type.
 *
 * @FieldType(
 *   id = "yamaps",
 *   label = @Translation("Yandex Map"),
 *   description = @Translation("This field stores Yandex Maps in the database."),
 *   default_widget = "yamaps_default",
 *   default_formatter = "yamaps_default"
 * )
 */
class YamapsFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
        'enable_placemarks' => TRUE,
        'enable_lines' => TRUE,
        'enable_polygons' => TRUE,
      ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['enable_placemarks'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable "Placemark" tool'),
      '#default_value' => $this->getSetting('enable_placemarks'),
    ];

    $element['enable_lines'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable "Line" tool'),
      '#default_value' => $this->getSetting('enable_lines'),
    ];

    $element['enable_polygons'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable "Polygon" tool'),
      '#default_value' => $this->getSetting('enable_lines'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field) {
    return [
      'columns' => [
        'coords' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'description' => 'Coordinates for "Yandex Maps" object.',
        ],
        'type' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => 'yandex#map',
          'description' => 'Type of "Yandex Maps" object.',
        ],
        'placemarks' => [
          'type' => 'text',
          'not null' => TRUE,
          'description' => 'Settings and data for "Yandex Maps" placemark.',
        ],
        'lines' => [
          'type' => 'text',
          'not null' => TRUE,
          'description' => 'Settings and data for "Yandex Maps" lines.',
        ],
        'polygons' => [
          'type' => 'text',
          'not null' => TRUE,
          'description' => 'Settings and data for "Yandex Maps" polygons.',
        ],
        'hide' => [
          'type' => 'int',
          'default' => 0,
          'description' => 'Flag defining whether "Yandex Maps" field should be hidden for end user or not.',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('coords')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['coords'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Coordinates'));

    $properties['type'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Type of "Yandex Maps" object'));

    $properties['placemarks'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Placemarks'));

    $properties['lines'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Lines'));

    $properties['polygons'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Polygons'));

    $properties['hide'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Placemarks'));

    return $properties;
  }

}
