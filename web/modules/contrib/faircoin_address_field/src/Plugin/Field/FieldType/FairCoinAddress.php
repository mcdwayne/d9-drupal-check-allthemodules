<?php

/**
 * @file
 * Drupal\faircoin_address_field\Plugin\Field\FieldType\FairCoinAddress.
 */

namespace Drupal\faircoin_address_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'faircoin_address' field type.
 *
 * @FieldType(
 *   id = "faircoin_address",
 *   label = @Translation("FairCoin address"),
 *   module = "faircoin_address_field",
 *   description = @Translation("Defines a field type for FairCoin addresses."),
 *   default_widget = "faircoin_address_field_simple_text",
 *   default_formatter = "faircoin_address_field_text_and_qrcode"
 * )
 */
class FairCoinAddress extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'text',
          'lenght' => 34,
          'not null' => FALSE,
        ),
      ),
    );
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
      ->setLabel(t('FairCoin address'));

    return $properties;
  }

}
