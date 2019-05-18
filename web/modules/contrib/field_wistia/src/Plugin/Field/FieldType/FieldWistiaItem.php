<?php
/**
 * @file
 * Contains \Drupal\field_wistia\Plugin\Field\FieldType\FieldWistiaItem.
 */

namespace Drupal\field_wistia\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_wistia' field type.
 *
 * @FieldType(
 *   id = "field_wistia",
 *   label = @Translation("Field wistia"),
 *   description = @Translation("Thie field stores a Wistia video in the database"),
 *   default_widget = "field_wistia",
 *   default_formatter = "field_wistia_video"
 * )
 */
class FieldWistiaItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['input'] = DataDefinition::create('string')
      ->setLabel(t('Video url'));

    $properties['video_id'] = DataDefinition::create('string')
      ->setLabel(t('Video id'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'input' => [
          'description' => 'Video URL.',
          'type' => 'varchar',
          'length' => 1024,
          'not null' => FALSE,
        ],
        'video_id' => [
          'description' => 'Video ID.',
          'type' => 'varchar',
          'length' => 20,
          'not null' => FALSE,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
//  public function isEmpty() {
//    $value = $this->get('value')->getValue();
//    return $value === NULL || $value === '';
//  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'input';
  }

}
