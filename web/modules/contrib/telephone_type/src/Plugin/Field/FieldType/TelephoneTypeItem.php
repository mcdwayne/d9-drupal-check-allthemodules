<?php

namespace Drupal\telephone_type\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\telephone\Plugin\Field\FieldType\TelephoneItem;

/**
 * Plugin implementation of the 'telephone_type' field type.
 *
 * @FieldType(
 *   id = "telephone_type",
 *   label = @Translation("Telephone number with type"),
 *   description = @Translation("This field stores a telephone number and type in the database."),
 *   category = @Translation("Number"),
 *   default_widget = "telephone_type_default",
 *   default_formatter = "telephone_type_link",
 *   constraints = {"TelephoneTypeValidation" = {}}
 * )
 */
class TelephoneTypeItem extends TelephoneItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['type'] = [
      'type' => 'varchar',
      'length' => 255,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $type_definition = DataDefinition::create('string')
      ->setLabel(t('Type'))
      ->setRequired(TRUE);
    $properties['type'] = $type_definition;

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $type = $this->get('type')->getValue();
    if ($type === NULL || $type === '') {
      if ($this->getSetting('type_required')) {
        return TRUE;
      }
    }
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   *
   * Store the entered value in NATIONAL format.
   */
  public function preSave() {
    $validator = \Drupal::service('telephone_type.validator');
    $this->value = $validator->getNationalNumber($this->value);
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = parent::generateSampleValue($field_definition);
    $values['type'] = array_rand(telephone_types_options());
    return $values;
  }

}
