<?php

/**
 * @file
 * Contains Drupal\ooyala\Plugin\Field\FieldType\OoyalaVideoField.
 */

namespace Drupal\ooyala\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\OptGroup;

/**
 * Provides a field type of Ooyala Video
 *
 * @FieldType(
 *   id = "ooyala_video",
 *   label = @Translation("Ooyala Video"),
 *   module = "ooyala",
 *   description = @Translation("Displays an Ooyala Video."),
 *   default_widget = "ooyala_video_select",
 *   default_formatter = "ooyala_video_formatter",
 *   category = @Translation("Reference")
 * )
 */
class OoyalaVideoField extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        // Store the full item entry here
        'item' => [
          'type' => 'blob',
          'not null' => TRUE,
        ],
        // And display settings
        'initial_time' => [
          'type' => 'int',
        ],
        'initial_volume' => [
          'type' => 'int',
        ],
        'initial_volume' => [
          'type' => 'int',
        ],
        'autoplay' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
        'loop' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
        'additional_params' => [
          'type' => 'text',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'item';
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->item);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['item'] = DataDefinition::create('string')
      ->setLabel(t('Item'))
      ->setDescription(t('The JSON-encoded Ooyala item record of the video to attach'));

    $properties['initial_time'] = DataDefinition::create('string')
      ->setLabel(t('Initial Time'))
      ->setDescription(t('The initial time (in seconds) to start the video'));

    $properties['initial_volume'] = DataDefinition::create('string')
      ->setLabel(t('Initial Volume'))
      ->setDescription(t('The initial volume (1-100) of the video'));

    $properties['autoplay'] = DataDefinition::create('string')
      ->setLabel(t('Autoplay?'))
      ->setDescription(t('Whether or not to autoplay the video'));

    $properties['loop'] = DataDefinition::create('string')
      ->setLabel(t('Loop?'))
      ->setDescription(t('Whether or not to loop the video'));

    $properties['additional_params'] = DataDefinition::create('string')
      ->setLabel(t('Additional Parameters'))
      ->setDescription(t('Any additional parameters for the player object'));

    return $properties;
  }

}
