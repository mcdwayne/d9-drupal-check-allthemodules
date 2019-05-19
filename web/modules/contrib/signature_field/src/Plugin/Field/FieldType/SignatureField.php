<?php

namespace Drupal\signature_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_signature' field type.
 *
 * @FieldType(
 *   id = "field_signature",
 *   label = @Translation("Signature Field"),
 *   module = "signature_field",
 *   description = @Translation("Demonstrates Signature Blob."),
 *   default_widget = "field_signature_field_widget",
 *   default_formatter = "field_signature_field_formatter"
 * )
 */
class SignatureField extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'text',
          'size' => 'big',
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
      ->setLabel(t('Signature'));

    return $properties;
  }

}
