<?php

namespace Drupal\jqueryui_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'Jqueryui Field' field type.
 *
 * @FieldType(
 *   id = "jqueryui_field",
 *   label = @Translation("Jqueryui Field"),
 *   description = @Translation("This field stores Jqueryui Fields in the database."),
 *   default_widget = "jqueryui_field_accordion",
 *   default_formatter = "jqueryui_field_accordion"
 * )
 */
class JqueryuiFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field) {
    return [
      'columns' => [
        'label' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'description' => [
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
    $value = $this->get('label')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['label'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Label'));

    $properties['description'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Description'));

    return $properties;
  }

}
