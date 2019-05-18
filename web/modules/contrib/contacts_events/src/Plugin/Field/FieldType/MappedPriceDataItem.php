<?php

namespace Drupal\contacts_events\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'mapped_price_data' field type.
 *
 * @FieldType(
 *   id = "mapped_price_data",
 *   label = @Translation("Mapped price data"),
 *   description = @Translation("Store the sources for mapped prices and their status."),
 *   category = @Translation("Events"),
 *   default_widget = "mapped_price_data",
 *   default_formatter = "mapped_price_data",
 *   list_class = "\Drupal\contacts_events\Plugin\Field\FieldType\MappedPriceDataItemList",
 *   cardinality = 1
 * )
 */
class MappedPriceDataItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['booking_window'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Booking window'))
      ->setRequired(TRUE);

    $properties['booking_window_overridden'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Booking window overridden'));

    $properties['class'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Class'))
      ->setRequired(FALSE);

    $properties['class_overridden'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Class overridden'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'booking_window' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'booking_window_overridden' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
        'class' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'class_overridden' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
      ],
    ];

    return $schema;
  }

}
