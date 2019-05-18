<?php

/**
 * @file
 * Contains Drupal\itchio_field\Plugin\Field\FieldType\ItchioItem.
 */

namespace Drupal\itchio_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'itchio_field_itchio' field type.
 *
 * @FieldType(
 *   id = "itchio_field_itchio",
 *   label = @Translation("Itchio Field"),
 *   module = "itchio_field",
 *   description = @Translation("A field for an itch project number"),
 *   default_widget = "itchio_iframe",
 *   default_formatter = "itchio_formatter"
 * )
 */
class ItchioItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'int',
          'size' => 'normal',
          'unsigned' => TRUE,
          'not null' => FALSE,
        ],
        'linkback' => [
          'type' => 'int',
          'not null' => FALSE,
          'default' => 0,
        ],
        'borderwidth' => [
          'type' => 'int',
          'not null' => FALSE,
          'default' => 0,
        ],
        'bg_color' => [
          'type' => 'varchar',
          'length' => 6,
          'not null' => FALSE,
          'default' => 'c9c9c9',
        ],
        'fg_color' => [
          'type' => 'varchar',
          'length' => 6,
          'not null' => FALSE,
          'default' => '000000',
        ],
        'link_color' => [
          'type' => 'varchar',
          'length' => 6,
          'not null' => FALSE,
          'default' => '0b628e',
        ],
        'border_color' => [
          'type' => 'varchar',
          'length' => 6,
          'not null' => FALSE,
          'default' => 'a0a0a0',
        ],
        'width' => [
          'type' => 'int',
          'not null' => FALSE,
          'default' => 552,
        ],
        'height' => [
          'type' => 'int',
          'not null' => FALSE,
          'default' => 167,
        ],
        'use_button' => [
          'type' => 'int',
          'not null' => FALSE,
          'default' => 0,
        ],
        'button_text' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
          'default' => 'Buy',
        ],
        'button_user' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
          'default' => 'test',
        ],
        'button_project' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
          'default' => 'test',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if (empty($this->get('value')->getValue()) && empty($this->get('button_project')->getValue())) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Itch project number'));
    $properties['linkback'] = DataDefinition::create('boolean')
      ->setLabel(t('Include link to itch.io page'));
    $properties['borderwidth'] = DataDefinition::create('string')
      ->setLabel(t('Border Width'));
    $properties['bg_color'] = DataDefinition::create('string')
      ->setLabel(t('Background color'));
    $properties['fg_color'] = DataDefinition::create('string')
      ->setLabel(t('Foreground color'));
    $properties['link_color'] = DataDefinition::create('string')
      ->setLabel(t('Link color'));
    $properties['border_color'] = DataDefinition::create('string')
      ->setLabel(t('Border color'));
    $properties['width'] = DataDefinition::create('string')
      ->setLabel(t('Width'));
    $properties['height'] = DataDefinition::create('string')
      ->setLabel(t('Height'));
    $properties['use_button'] = DataDefinition::create('string')
      ->setLabel(t('Use Button'));
    $properties['button_text'] = DataDefinition::create('string')
      ->setLabel(t('Button Text'));
    $properties['button_user'] = DataDefinition::create('string')
      ->setLabel(t('Itch.io Username'));
    $properties['button_project'] = DataDefinition::create('string')
      ->setLabel(t('Itch.io Project Name'));

    return $properties;
  }

}
