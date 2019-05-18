<?php

namespace Drupal\ext_redirect\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'source_path' field type.
 *
 * @FieldType(
 *   id = "source_path",
 *   label = @Translation("Source Path"),
 *   description = @Translation("Stores Redirect Rule source paths. Separated by newline."),
 *   default_widget = "source_path_widget",
 *   default_formatter = "source_path_formatter"
 * )
 */
class SourcePath extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['source_path'] = DataDefinition::create('string')
      ->setLabel(t('Source paths'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'source_path' => [
          'description' => 'Source path. May store multi paths, separated by newline.',
          'type' => 'text',
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $segments = rand(2, 8);
    $segments_names = [];
    for($i = 0; $i < $segments; $i++) {
      $segments_names[] = $random->string(8, TRUE);
    }
    $values['source_path'] = '/' . implode('/', $segments_names);
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('source_path')->getValue();
    return $value === NULL || $value === '';
  }

  public function getValue() {
    return $this->get('source_path')->getString();
  }


}
