<?php

namespace Drupal\file_attributes\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Plugin alteration of 'FileItem' field type.
 */
class FileAttributesFieldType extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    // This column allow add options. 
    $schema['columns'] += [
      'options' => [
        'type' => 'blob',
        'size' => 'big',
        'serialize' => TRUE,
        'description' => 'Field options for files',
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['options'] = MapDataDefinition::create()
      ->setLabel(t('Options'));
    return $properties;
  }

}
