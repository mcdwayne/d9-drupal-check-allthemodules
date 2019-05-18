<?php

namespace Drupal\flickr_stream\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface as StorageDefinition;

/**
 * Plugin implementation of the 'address' field type.
 *
 * @FieldType(
 *   id = "FlickrStream",
 *   label = @Translation("Flickr Stream"),
 *   description = @Translation("Stores flickr field configurations."),
 *   category = @Translation("Custom"),
 *   default_widget = "FlickrStreamDefaultWidget",
 *   default_formatter = "FlickrStreamDefaultFormatter"
 * )
 */
class FlickrStream extends FieldItemBase {

  const CHAR_FIELDS_MAXLENGTH = 255;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(StorageDefinition $storage) {

    $properties = [];

    $properties['flickr_stream_user_id'] = DataDefinition::create('string')
      ->setLabel(t('Flickr user id.'))->setDescription(t('User ID from flickr'))->setRequired(TRUE);

    $properties['flickr_stream_photoset_id'] = DataDefinition::create('string')
      ->setLabel(t('Flickr album id.'))->setDescription(t('Album ID from flickr'))->setRequired(FALSE);

    $properties['flickr_stream_photo_count'] = DataDefinition::create('string')
      ->setLabel(t('Photo count.'))->setDescription(t('Count photo to get from flickr'))->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(StorageDefinition $storage) {

    $columns = [];
    $columns['flickr_stream_user_id'] = [
      'type' => 'char',
      'not null' => TRUE,
      'length' => static::CHAR_FIELDS_MAXLENGTH,
    ];
    $columns['flickr_stream_photoset_id'] = [
      'type' => 'char',
      'not null' => FALSE,
      'length' => static::CHAR_FIELDS_MAXLENGTH,
    ];
    $columns['flickr_stream_photo_count'] = [
      'type' => 'char',
      'not null' => FALSE,
      'length' => static::CHAR_FIELDS_MAXLENGTH,
    ];

    return [
      'columns' => $columns,
      'indexes' => [],
    ];
  }

  /**
   * Define when the field type is empty.
   *
   * This method is important and used internally by Drupal. Take a moment
   * to define when the field type must be considered empty.
   */
  public function isEmpty() {

    $isEmpty =
      empty($this->get('flickr_stream_user_id')->getValue()) &&
      empty($this->get('flickr_stream_photoset_id')->getValue()) &&
      empty($this->get('flickr_stream_photo_count')->getValue());

    return $isEmpty;
  }

}
