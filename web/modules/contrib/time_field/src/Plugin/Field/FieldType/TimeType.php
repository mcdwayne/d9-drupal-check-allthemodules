<?php

namespace Drupal\time_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'time' field type.
 *
 * @FieldType(
 *   category= @Translation("General"),
 *   id = "time",
 *   label = @Translation("Time"),
 *   description = @Translation("Time field"),
 *   default_widget = "time_widget",
 *   default_formatter = "time_formatter"
 * )
 */
class TimeType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Seconds passed through midnight'))
      ->setSetting('unsigned', TRUE)
      ->setSetting('size', 'small')
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'value' => [
          'type' => 'int',
          'length' => 6,
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    $constraint_manager = \Drupal::typedDataManager()
      ->getValidationConstraintManager();

    $constraints[] = $constraint_manager->create('ComplexData', [
      'value' => ['time' => []],
    ]);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['value'] = 43200;
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return trim($value) === '';
  }

}
