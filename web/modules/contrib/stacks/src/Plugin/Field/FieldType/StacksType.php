<?php

namespace Drupal\stacks\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'stacks_type' field type.
 *
 * @FieldType(
 *   id = "stacks_type",
 *   label = @Translation("Stacks"),
 *   description = @Translation("Add stacks to an entity."),
 *   default_widget = "form_widget_type",
 *   default_formatter = "widget_formatter_type"
 * )
 */
class StacksType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['widget_instance_id'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Widget Entity Instance ID'))
      ->setDescription(new TranslatableMarkup('Which stacks entity instance is this attached to?'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'widget_instance_id' => [
          'type' => 'int',
          'not null' => TRUE,
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
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('widget_instance_id')->getValue();
    return $value === NULL || $value === '';
  }

}
