<?php

namespace Drupal\field_nif\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'nif' field type.
 *
 * @FieldType(
 *   id = "nif",
 *   label = @Translation("NIF/CIF/NIE"),
 *   description = @Translation("A field containing a NIF/CIF/NIE value"),
 *   default_widget = "nif_default",
 *   default_formatter = "nif_default"
 * )
 */
class NifItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Computed property for an easier access to the complete document number.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('NIF value'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\field_nif\NifProcessed')
      ->setSetting('document source', ['first_letter', 'number', 'last_letter']);
    $properties['number'] = DataDefinition::create('string')
      ->setLabel(t('Document number'));
    $properties['first_letter'] = DataDefinition::create('string')
      ->setLabel(t('First letter of the NIF/CIF/NIE'));
    $properties['last_letter'] = DataDefinition::create('string')
      ->setLabel(t('Last letter of the NIF/CIF/NIE'));
    $properties['type'] = DataDefinition::create('string')
      ->setLabel(t('Type of document'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'number' => [
          'type' => 'varchar',
          'length' => 8,
        ],
        'first_letter' => [
          'type' => 'varchar',
          'length' => 1,
        ],
        'last_letter' => [
          'type' => 'varchar',
          'length' => 1,
        ],
        'type' => [
          'type' => 'varchar',
          'length' => 3,
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

    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints[] = $constraint_manager->create('ComplexData', [
      'value' => [
        'NifValue' => [
          'supportedTypes' => array_filter($this->getSetting('supported_types')),
        ],
      ],
    ]);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'supported_types' => ['nif', 'cif', 'nie'],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['supported_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Supported document types'),
      '#default_value' => $this->getSetting('supported_types'),
      '#options' => [
        'nif' => $this->t('NIF'),
        'cif' => $this->t('CIF'),
        'nie' => $this->t('NIE'),
      ],
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
