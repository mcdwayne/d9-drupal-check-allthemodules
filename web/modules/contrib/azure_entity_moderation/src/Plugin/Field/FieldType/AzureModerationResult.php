<?php

namespace Drupal\azure_entity_moderation\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'azure_entity_moderation' field type.
 *
 * @FieldType(
 *   id = "azure_entity_moderation",
 *   label = @Translation("Azure entity moderation result"),
 *   module = "azure_entity_moderation",
 *   description = @Translation("Provides storage for automatic moderation results."),
 *   default_widget = "azure_entity_moderation",
 *   default_formatter = "azure_entity_moderation_number"
 * )
 */
class AzureModerationResult extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'fields' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    // Get all text type fields first.
    $options = [];
    $property_definitions = $this->getParent()->getParent()->getDataDefinition()->getPropertyDefinitions();
    foreach ($property_definitions as $id => $definition) {
      if (substr($definition->getType(), 0, 6) === 'string' || substr($definition->getType(), 0, 4) === 'text') {
        $options[$id] = $definition->getLabel();
      }
    }

    $element = [];
    $settings = $this->getSettings();

    $element['fields'] = [
      '#type' => 'checkboxes',
      '#title' => t('Moderated fields'),
      '#options' => $options,
      '#default_value' => $settings['fields'],
      '#description' => t('Select entity fields that should be checked by the Azure text API.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'float',
          'unsigned' => TRUE,
          'size' => 'normal',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('float')
      ->setLabel(t('Moderation score'));

    return $properties;
  }

}
