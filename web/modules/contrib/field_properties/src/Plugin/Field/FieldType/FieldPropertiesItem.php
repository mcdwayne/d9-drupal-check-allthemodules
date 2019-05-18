<?php

/**
 * @file
 * Contains \Drupal\email\Plugin\field\field_type\ConfigurableEmailItem.
 */

namespace Drupal\field_properties\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_properties' field type.
 *
 * @FieldType(
 *   id = "field_properties",
 *   label = @Translation("Properties"),
 *   description = @Translation("This field stores arbitrary properties."),
 *   default_widget = "field_properties_widget",
 *   default_formatter = "field_properties_formatter"
 * )
 */
class FieldPropertiesItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['name'] = DataDefinition::create('string')
      ->setLabel(t('Name'));

    $properties['type'] = DataDefinition::create('string')
      ->setLabel(t('Type'));

    $properties['value'] = DataDefinition::create('any')
      ->setLabel(t('Value'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'name' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ),
        'type' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ),
        'value' => array(
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ),
      ),
      'indexes' => array(
        'name' => array('name'),
        'type' => array('type'),
      ),
      'foreign keys' => array(),
    );

  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $name = $this->get('name')->getValue();
    return $name === NULL || $name === '';
  }

  /**
   * Defines the field-level settings for this plugin.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultSettings() {
    return array();
  }

  /**
   * Defines the instance-level settings for this plugin.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultInstanceSettings() {
    return array();
  }

}
