<?php

namespace Drupal\bigint\Plugin\Field\FieldType;


use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\NumericItemBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'bigint' field type.
 *
 * @FieldType(
 *   id = "bigint",
 *   label = @Translation("Number (bigint)"),
 *   description = @Translation("This field stores a big integer number in the database."),
 *   category = @Translation("Number"),
 *   default_widget = "bigint",
 *   default_formatter = "bigint_item_default"
 * )
 */
class BigIntItem extends NumericItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
        'unsigned' => TRUE,
        'size' => 'big',
      ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('BigInt value'))
      ->setRequired(TRUE);

    return $properties;

  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    if ($this->getSetting('unsigned')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Range' => [
            'min' => 0,
            'minMessage' => t('%name: The integer must be larger or equal to %min.', [
              '%name' => $this->getFieldDefinition()->getLabel(),
              '%min' => 0,
            ]),
          ],
        ],
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'int',
          'unsigned' => $field_definition->getSetting('unsigned'),
          'size' => $field_definition->getSetting('size'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $min = $field_definition->getSetting('min') ?: 0;
    $max = $field_definition->getSetting('max') ?: 999;
    $values['value'] = mt_rand($min, $max);
    return $values;
  }

}