<?php

namespace Drupal\onehub\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'onehub_select' field type.
 *
 * @FieldType(
 *   id = "onehub_select",
 *   label = @Translation("OneHub Select"),
 *   description = @Translation("This field allows you to select all files within a folder to display."),
 *   category = @Translation("Reference"),
 *   default_widget = "onehub_select",
 *   default_formatter = "onehub_select_formatter",
 * )
 */
class OneHubSelect extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'workspace' => [
          'description' => "The Workspace for the OneHub file",
          'type' => 'varchar',
          'length' => 512,
        ],
        'folder' => [
          'description' => "The OneHub folder this is in.",
          'type' => 'varchar',
          'length' => 1024,
        ],
      ],
      'indexes' => [
        'workspace' => ['workspace'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['workspace'] = DataDefinition::create('string')
      ->setLabel(t('Workspace'));

    $properties['folder'] = DataDefinition::create('string')
      ->setLabel(t('Folder'));

    return $properties;
  }
}