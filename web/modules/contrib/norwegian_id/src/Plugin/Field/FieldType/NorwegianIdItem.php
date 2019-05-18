<?php

namespace Drupal\norwegian_id\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItemBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'norwegian_personal_id' field type.
 *
 * @FieldType(
 *   id = "norwegian_id",
 *   label = @Translation("Norwegian Personal ID"),
 *   description = @Translation("Stores a Norwegian personal ID"),
 *   default_widget = "norwegian_id_textfield",
 *   default_formatter = "norwegian_id_default"
 * )
 */
class NorwegianIdItem extends StringItemBase {

  const ID_LENGTH = 11;

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'allow_d_number' => FALSE,
      'max_length'     => self::ID_LENGTH,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar_ascii',
          'length' => (int) $field_definition->getSetting('max_length'),
          'binary' => $field_definition->getSetting('case_sensitive'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints[] = $constraint_manager->create('ComplexData', [
      'value' => [
        'Length' => [
          'min' => self::ID_LENGTH,
          'max' => self::ID_LENGTH,
          'exactMessage' => t('%name: must be @len characters.', [
            '%name' => $this->getFieldDefinition()->getLabel(),
            '@len' => self::ID_LENGTH,
          ]),
        ],
      ],
    ]);

    $constraints[] = $constraint_manager->create('ComplexData', [
      'value' => [
        'NorwegianId' => [],
      ],
    ]);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    // @todo: Generate some real ones!
    $values['value'] = $random->word(mt_rand(1, self::ID_LENGTH));
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];

    $elements['allow_d_number'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow D-Number'),
      '#default_value' => $this->getSetting('allow_d_number'),
      '#required' => TRUE,
      '#description' => t('Not yet supported.'),
      '#disabled' => TRUE || $has_data,
    ];

    return $elements;
  }

}
