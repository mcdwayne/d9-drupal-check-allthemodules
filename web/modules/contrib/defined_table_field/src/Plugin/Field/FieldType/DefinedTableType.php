<?php

namespace Drupal\defined_table\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\defined_table\Plugin\Field\DefinedTableSourceSelectionTrait;

/**
 * Plugin implementation of the 'defined_table' field type.
 *
 * @FieldType (
 *   id = "defined_table",
 *   label = @Translation("Defined Table"),
 *   description = @Translation("Stores a predefined table of text fields"),
 *   default_widget = "defined_table",
 *   default_formatter = "defined_table",
 *   list_class = "\Drupal\defined_table\Plugin\Field\FieldType\DefinedTableFieldItemList"
 * )
 */
class DefinedTableType extends FieldItemBase {

  use DefinedTableSourceSelectionTrait;

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'header' => [
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
        'arguments' => [
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
        'values' => [
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'input_type' => 'textfield',
      'header' => [
        'type' => 'values',
        'data' => '',
      ],
      'arguments' => [
        'type' => 'values',
        'data' => '',
      ],
      'on_label' => '',
      'off_label' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $settings = $form_state->getValue('settings');
    if (empty($settings)) {
      $settings = $this->getSettings();
    }

    $form['input_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Input type'),
      '#required' => TRUE,
      '#options' => [
        'textfield' => $this->t('Text field'),
        'textarea' => $this->t('Text area'),
        'checkbox' => $this->t('Checkbox'),
      ],
      '#default_value' => $settings['input_type'],
    ];

    $form['on_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('"On" label'),
      '#default_value' => $this->getSetting('on_label'),
      '#states' => [
        // Only show this field when the 'input_type' is checkbox.
        'visible' => [
          ':input[name="settings[input_type]"]' => [
            'value' => 'checkbox',
          ],
        ],
      ],
    ];
    $form['off_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('"Off" label'),
      '#default_value' => $this->getSetting('off_label'),
      '#states' => [
        // Only show this field when the 'input_type' is checkbox.
        'visible' => [
          ':input[name="settings[input_type]"]' => [
            'value' => 'checkbox',
          ],
        ],
      ],
    ];

    foreach (['header' => $this->t('Header values'), 'arguments' => $this->t('Argument labels')] as $axis => $label) {
      $form[$axis] = $this->buildSourceSelector($label, $settings[$axis]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['header'] = MapDataDefinition::create()
      ->setLabel(t('Table header'))
      ->setDescription(t('Stores table header data.'));
    $properties['arguments'] = MapDataDefinition::create()
      ->setLabel(t('Argument labels'))
      ->setDescription(t('Stores table argument labels.'));
    $properties['values'] = MapDataDefinition::create()
      ->setLabel(t('Table data'))
      ->setDescription(t('Stores tabular data.'));

    return $properties;
  }

  /**
   * Value setter.
   */
  public function setValue($values, $notify = TRUE) {
    if (!isset($values)) {
      return;
    }
    elseif (!empty($values['table']['table'])) {
      $processed_values = [];
      foreach ($values['table']['table'] as $key => $row) {
        unset($row[0]);
        $processed_values['values'][$key] = $row;
      }

      foreach (['header', 'arguments'] as $axis) {
        if (isset($values['axes_settings'][$axis])) {
          $processed_values[$axis] = $values['axes_settings'][$axis];
        }
      }
      $values = $processed_values;
    }

    // The unserialize fix.
    foreach (['header', 'arguments', 'values'] as $column) {
      if (isset($values[$column]) && is_string($values[$column])) {
        $values[$column] = unserialize($values[$column]);
      }
    }

    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // @TODO should field definition be counted?
    return [
      'header' => ['Header 1', 'Header 2'],
      'arguments' => ['Argument 1', 'Argument 2'],
      'values' => [['11', '12'], ['21', '22']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $values = $this->get('values')->getValue();
    if (!is_array($values)) {
      return TRUE;
    }

    foreach ($values as $row) {
      foreach ($row as $cell) {
        if (!empty($cell)) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

}
