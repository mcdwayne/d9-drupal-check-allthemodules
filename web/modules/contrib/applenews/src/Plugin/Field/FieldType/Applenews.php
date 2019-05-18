<?php

namespace Drupal\applenews\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;

/**
 * Plugin implementation of the 'applenews' field type.
 *
 * @FieldType(
 *   id = "applenews_default",
 *   label = @Translation("Apple News"),
 *   description = @Translation("This field manages configuration and presentation of applenews."),
 *   default_widget = "applenews_default",
 *   cardinality = 1,
 * )
 */
class Applenews extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['status'] = DataDefinition::create('boolean')
      ->setLabel(t('Applenews value'));
    $properties['template'] = DataDefinition::create('string')
      ->setLabel(t('Template'));
    $properties['channels'] = DataDefinition::create('string')
      ->setLabel(t('channels'));
    $properties['is_preview'] = DataDefinition::create('boolean')
      ->setLabel(t('Preview'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'status' => 0,
      'template' => NULL,
      'channels' => NULL,
      'is_preview' => 1,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'status' => [
          'description' => 'Publish to Apple News',
          'type' => 'int',
          'default' => 0,
        ],
        'template' => [
          'description' => 'Template name',
          'type' => 'varchar',
          'length' => 128,
        ],
        'channels' => [
          'description' => 'Channels',
          'type' => 'text',
          'size' => 'big',
        ],
        'is_preview' => [
          'description' => 'Content visibility',
          'type' => 'int',
          'default' => 1,
        ],
      ],
      'indexes' => [],
      'foreign keys' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'status';
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    return [
      'status' => mt_rand(0, 1),
      'template' => 'Blog',
      'channels' => '',
    ];
  }

}
