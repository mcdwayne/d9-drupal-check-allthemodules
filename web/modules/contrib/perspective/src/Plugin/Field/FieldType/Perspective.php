<?php

namespace Drupal\perspective\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'perspective' field type.
 *
 * @FieldType(
 *   id = "perspective",
 *   label = @Translation("Perspective"),
 *   module = "perspective",
 *   description = @Translation("Defines a field that analyzes and scores the text."),
 *   default_widget = "perspective_text",
 *   default_formatter = "perspective_formatted_text"
 * )
 */
class Perspective extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Text'));

    return $properties;
  }

}
