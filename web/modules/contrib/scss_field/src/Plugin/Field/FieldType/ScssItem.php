<?php

namespace Drupal\scss_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a SCSS field type.
 *
 * @FieldType(
 *   id = "scss",
 *   label = @Translation("SCSS"),
 *   default_widget = "scss",
 *   default_formatter = "scss",
 *   constraints = {"Scss" = {}}
 * )
 */
class ScssItem extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('SCSS'))
      ->setRequired(TRUE);

    $properties['scoped'] =

    $properties['compiled'] = DataDefinition::create('string')
      ->setLabel(t('Compiled CSS'))
      ->setDescription(t('The CSS compiled from the SCSS source.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\scss_field\ScssCompiled');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    // The key of the element should be the setting name.
    $element['scoped'] = [
      '#title' => $this->t('Scoped'),
      '#description' => $this->t('Whether the provided SCSS will be scoped/limited to this entity only.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('scoped'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'scoped' => TRUE,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
