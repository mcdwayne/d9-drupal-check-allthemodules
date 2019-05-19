<?php

namespace Drupal\sir_trevor\Plugin\Field\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of baz.
 *
 * @FieldType(
 *   id = "sir_trevor",
 *   label = @Translation("Sir Trevor"),
 *   default_formatter = "sir_trevor",
 *   default_widget = "sir_trevor",
 * )
 */
class SirTrevor extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['json'] = DataDefinition::create('string');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'json' => [
          'type' => 'text',
          'size' => 'big',
        ],
      ],
    ];
  }
}
