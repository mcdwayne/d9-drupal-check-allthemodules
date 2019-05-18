<?php

namespace Drupal\commerce_rental\Plugin\Field\FieldType;

use Drupal\commerce_price\Plugin\Field\FieldType\PriceItem;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'commerce_rental_rate' field type.
 *
 * @FieldType(
 *   id = "commerce_rental_rate",
 *   label = @Translation("Rental Rate"),
 *   description = @Translation("Stores a rental period id, decimal number, and three letter currency code."),
 *   category = @Translation("Commerce"),
 *   default_widget = "rental_rate_default",
 *   default_formatter = "rental_rate_view",
 * )
 */

class RentalRateItem extends PriceItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['period_id'] = DataDefinition::create('integer')
      ->setSetting('unsigned', TRUE)
      ->setLabel(t('Rental period id'));

    return $properties;
  }

  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['period_id'] = [
      'description' => 'The ID of the rental period.',
      'type' => 'int',
      'unsigned' => TRUE,
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->number === NULL || $this->number === '' || empty($this->currency_code) || empty($this->period_id);
  }
}