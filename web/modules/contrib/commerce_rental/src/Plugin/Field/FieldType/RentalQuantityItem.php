<?php

namespace Drupal\commerce_rental\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\DecimalItem;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'commerce_rental_quantity' field type.
 *
 * @FieldType(
 *   id = "commerce_rental_quantity",
 *   label = @Translation("Rental Quantity"),
 *   description = @Translation("Stores a rental period id and quantity"),
 *   category = @Translation("Commerce"),
 *   default_widget = "rental_quantity_default",
 *   default_formatter = "rental_quantity_view",
 * )
 */

class RentalQuantityItem extends DecimalItem {

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

//  /**
//   * {@inheritdoc}
//   */
//  public function isEmpty() {
//    return empty($this->value) || empty($this->period_id);
//  }

}