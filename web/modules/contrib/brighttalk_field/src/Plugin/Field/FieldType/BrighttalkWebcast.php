<?php

namespace Drupal\brighttalk_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'brighttalk_field_webcast' field type.
 *
 * @FieldType(
 *   id = "brighttalk_webcast",
 *   label = @Translation("BrightTalk Webcast"),
 *   module = "BrightTalk Field",
 *   description = @Translation("BrightTalk webcast from embed code."),
 *   category = @Translation("Media"),
 *   default_widget = "brighttalk_embed",
 *   default_formatter = "brighttalk_embed"
 * )
 */
class BrighttalkWebcast extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $columns = [];

    $columns['embed'] = [
      'description' => 'Embed code.',
      'type'        => 'text',
      'size'        => 'big',
    ];

    $columns['channel_id'] = [
      'description' => 'Channel ID.',
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ];

    $columns['webcast_id'] = [
      'description' => 'Webcast ID.',
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ];

    return [
      'columns' => $columns,
      'indexes' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {

    $isEmpty = (
      empty($this->get('embed')->getValue()) &&
      empty($this->get('channel_id')->getValue()) &&
      empty($this->get('webcast_id')->getValue())
    );

    return $isEmpty;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['embed'] = DataDefinition::create('string')
      ->setLabel(t('Embed Code'));

    $properties['channel_id'] = DataDefinition::create('string')
      ->setLabel(t('Channel ID'));

    $properties['webcast_id'] = DataDefinition::create('string')
      ->setLabel(t('Webcast ID'));

    return $properties;
  }

}
