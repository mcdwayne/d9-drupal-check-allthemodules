<?php

namespace Drupal\taggd\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\image\Plugin\Field\FieldType\ImageItem;

/**
 * Plugin implementation of the 'taggd' field type.
 *
 * @FieldType(
 *   id = "taggd_image",
 *   label = @Translation("Taggd image"),
 *   description = @Translation("This field stores a Taggd image"),
 *   category = @Translation("Reference"),
 *   default_widget = "taggd_image",
 *   default_formatter = "taggd_image",
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class TaggdImageItem extends ImageItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['taggd_image_data'] = MapDataDefinition::create()
      ->setLabel(t('Taggd image info'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['taggd_image_data'] = [
      'description' => 'Serialized array of taggd images.',
      'type' => 'blob',
      'size' => 'big',
      'not null' => FALSE,
      'serialize' => TRUE,
    ];

    return $schema;
  }

}
