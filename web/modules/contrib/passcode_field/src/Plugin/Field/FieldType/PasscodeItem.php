<?php

namespace Drupal\passcode_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_passcode' field type.
 *
 * @FieldType(
 *   id = "field_passcode",
 *   label = @Translation("Passcode"),
 *   module = "passcode_field",
 *   description = @Translation("This field stores a randomly generated passcode."),
 *   default_widget = "field_passcode_text",
 *   default_formatter = "field_passcode_default"
 * )
 */
class PasscodeItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = [
      'passcode' => [
        'type' => 'varchar',
        'length' => 15,
        'not null' => TRUE,
      ],
    ];

    $schema = [
      'columns' => $columns,
      'indexes' => [
        'passcode' => ['passcode'],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('passcode')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['passcode'] = DataDefinition::create('string')
      ->setLabel(t('Passcode'));

    return $properties;
  }

}
