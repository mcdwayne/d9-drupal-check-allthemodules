<?php

namespace Drupal\entity_serial\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'entity_serial_field_type' field type.
 *
 * @FieldType(
 *   id = "entity_serial_field_type",
 *   label = @Translation("Entity serial (integer)"),
 *   description = @Translation("Generates serial number based on entity type or bundle."),
 *   category = @Translation("Number"),
 *   default_widget = "entity_serial_widget",
 *   default_formatter = "entity_serial_formatter"
 * )
 */
class EntitySerialFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'starts_with_id' => 1,
      'starts_with_entity_id' => 1,
      'unsigned' => FALSE,
      // Valid size property values include: 'tiny', 'small', 'medium', 'normal'
      // and 'big'.
      'size' => 'normal',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Serial value'))
      ->setRequired(TRUE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $settings = $field_definition->getSettings();
    $schema = [
      'columns' => [
        'value' => [
          'type' => 'int',
          'size' => $settings['size'],
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['value'] = rand(PHP_INT_MIN, PHP_INT_MAX);
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];

    $elements['starts_with_id'] = [
      '#type' => 'number',
      '#title' => t('Starts with id'),
      '#default_value' => $this->getSetting('starts_with_id'),
      '#required' => TRUE,
      '#description' => t('The serial number to start from.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];
    $elements['starts_with_entity_id'] = [
      '#type' => 'number',
      '#title' => t('Starts with entity'),
      '#default_value' => $this->getSetting('starts_with_entity_id'),
      '#required' => TRUE,
      '#description' => t('The entity id to start from.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === 0;
  }

}
