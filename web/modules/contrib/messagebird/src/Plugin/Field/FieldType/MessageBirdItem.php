<?php

namespace Drupal\messagebird\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\telephone\Plugin\Field\FieldType\TelephoneItem;

/**
 * Plugin implementation of the 'telephone' field type.
 *
 * @FieldType(
 *   id = "messagebird",
 *   label = @Translation("Telephone number (extended by MessageBird)"),
 *   description = @Translation("This field stores a telephone number in the database."),
 *   category = @Translation("Number"),
 *   default_widget = "telephone_messagebird_advanced",
 *   default_formatter = "messagebird_string"
 * )
 */
class MessageBirdItem extends TelephoneItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 256,
          'description' => 'e164 format with leading plus-sign',
        ),
        'country_code' => array(
          'type' => 'varchar',
          'length' => 2,
        ),
        'country_prefix' => array(
          'type' => 'numeric',
          'precision' => 3,
          'scale' => 0,
        ),
        'type' => array(
          'type' => 'varchar',
          'length' => '30',
        ),
        'number' => array(
          'type' => 'numeric',
          'precision' => 15,
          'scale' => 0,
        ),
        'international' => array(
          'type' => 'varchar',
          'length' => '30',
        ),
        'national' => array(
          'type' => 'varchar',
          'length' => '30',
        ),
        'rfc3966' => array(
          'type' => 'varchar',
          'length' => '30',
        ),
        'hlr_data' => array(
          'type' => 'blob',
          'serialize' => TRUE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Get field storage definition for 'value'.
    $properties = parent::propertyDefinitions($field_definition);

    $properties['country_code'] = DataDefinition::create('string')
      ->setLabel($this->t('Country code'));

    $properties['country_prefix'] = DataDefinition::create('string')
      ->setLabel($this->t('Country prefix'));

    $properties['type'] = DataDefinition::create('string')
      ->setLabel($this->t('Type'));

    $properties['number'] = DataDefinition::create('string')
      ->setLabel($this->t('Number format'));

    $properties['international'] = DataDefinition::create('string')
      ->setLabel($this->t('International format'));

    $properties['national'] = DataDefinition::create('string')
      ->setLabel($this->t('National format'));

    $properties['rfc3966'] = DataDefinition::create('string')
      ->setLabel($this->t('rfc3966 format'));

    $properties['hlr_data'] = DataDefinition::create('string')
      ->setLabel($this->t('HLR data'));

    return $properties;
  }

}
