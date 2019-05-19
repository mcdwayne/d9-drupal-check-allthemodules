<?php

namespace Drupal\unique_code_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\unique_code_field\Utilities;

/**
 * Plugin implementation of the 'unique_code' field type.
 *
 * @FieldType(
 *   id = "unique_code",
 *   label = @Translation("Unique code"),
 *   description = @Translation("Adds an auto-generated unique code"),
 *   default_widget = "unique_code_widget",
 *   default_formatter = "unique_code_formatter"
 * )
 */
class UniqueCode extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 60,
      'is_ascii' => FALSE,
      'case_sensitive' => FALSE,
      'code_type' => 'alphanumeric',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Text value'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setSetting('code_type', $field_definition->getSetting('code_type'))
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
          'type' => $field_definition->getSetting('is_ascii') === TRUE ? 'varchar_ascii' : 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'binary' => $field_definition->getSetting('case_sensitive'),
          'text' => $field_definition->getSetting('code_type'),
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

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Length' => [
            'max' => $max_length,
            'maxMessage' => t('%name: may not be longer than @max characters.', [
              '%name' => $this->getFieldDefinition()->getLabel(),
              '@max' => $max_length,
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
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $utilities = new Utilities();
    // Cast the string to numeric primitive data type.
    $length = (int) $field_definition->getSetting('max_length');
    $type = $field_definition->getSetting('code_type');
    $values = $utilities->generateCode($type, $length);
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];

    $elements['max_length'] = [
      '#type' => 'number',
      '#title' => t('Code length'),
      '#default_value' => $this->getSetting('max_length'),
      '#required' => TRUE,
      '#description' => t('The length of the unique code.'),
      '#max' => $this->getSetting('max_length'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];

    /*
    Let the user choose the type of code that will be generated. The options
    are used in the field widget to generate the unique code.
     */
    $elements['code_type'] = [
      '#type' => 'radios',
      '#title' => t('Code type'),
      '#default_value' => $this->getSetting('code_type'),
      '#options' => [
        'alphanumeric' => 'Numbers and letters',
        'numeric' => 'Numbers',
        'alphabetical' => 'Letters',
      ],
      '#description' => t('Choose the type of code that will be generated.'),
      '#required' => TRUE,
      '#disabled' => $has_data,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * Performs a check against the entity to which the field is attached.
   *
   * Gets the entity type to which the field is attached and check if this code
   * already exists in one of them.
   *
   * @param string $code
   *   The code we are going to check.
   * @param string $entity_type
   *   The entity type id against which the check is performed.
   * @param string $field_name
   *   The name assigned to Unique Code field.
   *
   * @return bool
   *   TRUE if the code does not exists in the entities fields.
   */
  public function isUnique($code, $entity_type, $field_name) {
    $is_unique = Utilities::isUnique($code, $entity_type, $field_name);
    return $is_unique;
  }

}
