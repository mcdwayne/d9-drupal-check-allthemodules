<?php

namespace Drupal\persian_fields\Plugin\Field\FieldType;

use Drupal\Core\Field\Annotation\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'melli_code' field type.
 *
 * @FieldType(
 *   category="Persian Fields",
 *   id = "iran_card_number",
 *   label = @Translation("Iran payment card number"),
 *   description = @Translation("Payment card number for Iranians"),
 *   default_widget = "iran_card_number_widget",
 *   default_formatter = "iran_card_number_formatter"
 * )
 */
class IranCardNumber extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Iran payment card number'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
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
          'type' => 'varchar',
          'length' => 16,
        ],
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
      'value' => [
        'iran_card_number' => [],
      ],
    ]);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['value'] = '6104337856448830';
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
