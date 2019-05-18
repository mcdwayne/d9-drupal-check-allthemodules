<?php

namespace Drupal\described_link\Plugin\Field\FieldType;

use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * @FieldType(
 *   id = "described_link",
 *   label = @Translation("Described link"),
 *   description = @Translation("Link field with a description."),
 *   default_widget = "described_link_default",
 *   default_formatter = "described_link_default"
 * )
 */
class DescribedLink extends LinkItem {
  /**
   * @inheritdoc
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['description'] = DataDefinition::create('string')
      ->setLabel(t('Link description'));
    return $properties;
  }

  /** 
   * @inheritdoc
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['description'] = [
      'type' => 'text',
    ];

    return $schema;
  }
}