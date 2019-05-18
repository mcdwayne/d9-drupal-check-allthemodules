<?php

namespace Drupal\icon_field\Plugin\Field\FieldType;

/**
 * @file
 * Contains \Drupal\Icon\Plugin\field\field_type\IconFieldItem.
 */

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'IconFieldItem' field type.
 *
 * @FieldType(
 *   id = "icon",
 *   label = @Translation("Icon"),
 *   description = @Translation("Store a icon in the database to assemble an icon field."),
 *   category = @Translation("Icon API"),
 *   default_widget = "icon_default_widget",
 *   default_formatter = "icon_field_default"
 * )
 */
class IconFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['icon'] = DataDefinition::create('string')
      ->setLabel(t('Icon'))
      ->setRequired(TRUE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'icon' => array(
          'type' => 'varchar',
          'length' => 64,
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('icon')->getValue();
    return $value === NULL || $value === '';
  }

}
