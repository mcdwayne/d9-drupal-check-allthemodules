<?php

/**
 * @file
 * Contains \Drupal\skype\Plugin\Field\FieldType\SkypeItem.
 */

namespace Drupal\skype\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'skype' field type.
 *
 * @FieldType(
 *   id = "skype",
 *   label = @Translation("Skype"),
 *   description = @Translation("This field stores a skype ID in the database."),
 *   category = @Translation("Text"),
 *   default_widget = "skype_default",
 *   default_formatter = "skype_button"
 * )
 */
class SkypeItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'not null' => TRUE,
          'default' => 0,
          'length' => 255,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Skype ID'));

    return $properties;
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
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['value'] = 'my.skype.id';
    return $values;
  }

}
