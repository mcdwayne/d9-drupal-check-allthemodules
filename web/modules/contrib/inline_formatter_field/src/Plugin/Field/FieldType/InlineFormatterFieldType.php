<?php

namespace Drupal\inline_formatter_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of table.
 *
 * @FieldType(
 *   id = "inline_formatter_field",
 *   label = @Translation("Inline Formatter"),
 *   description = @Translation("Stores Twig to display if a boolean is selected."),
 *   category = @Translation("Formatting"),
 *   default_formatter = "inline_formatter_field_formatter",
 *   default_widget = "inline_formatter_field_widget",
 * )
 */
class InlineFormatterFieldType extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'display_format' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
          'default' => 1,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->get('display_format')->getValue() == 1 ? FALSE : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['display_format'] = DataDefinition::create('boolean')
      ->setLabel(t('Whether or not to actually render the format given.'));

    return $properties;
  }

}
