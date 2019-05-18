<?php

namespace Drupal\private_content\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation for the PrivateItem field type.
 *
 * @FieldType(
 *   id = "private",
 *   label = @Translation("Private"),
 *   description = @Translation("A field type for storing whether a node is private."),
 *   default_widget = "private",
 *   default_formatter = "private",
 *   list_class = "\Drupal\private_content\Plugin\Field\FieldType\PrivateItemList"
 * )
 */
class PrivateItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'stored';
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'stored' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['stored'] = DataDefinition::create('boolean')
      ->setLabel(t('Stored value'))
      ->setRequired(TRUE);
    $properties['value'] = DataDefinition::create('boolean')
      ->setLabel(t('Usable value'))
      ->setDescription(t('Calculated value taking into account the content type settings.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\private_content\PrivateComputed');
    return $properties;
  }

}
