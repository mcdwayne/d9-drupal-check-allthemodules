<?php

namespace Drupal\dimension\Plugin\Field\FieldType;

use Drupal\dimension\Plugin\Field\Basic;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\DecimalItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 *
 */
abstract class Dimension extends DecimalItem implements Basic {

  /**
   * @inheritdoc
   */
  protected static function _defaultStorageSettings($fields) {
    $settings = array(
      'storage_value' => array(
        'precision' => 10,
        'scale' => 2,
      ),
    );
    foreach ($fields as $key => $label) {
      $settings['storage_' . $key] = array(
        'precision' => 10,
        'scale' => 2,
      );
    }
    return $settings;
  }

  /**
   * @inheritdoc
   */
  protected static function _defaultFieldSettings($fields) {
    $settings = array(
      'value' => array(
        'factor' => 1,
        'min' => '',
        'max' => '',
        'prefix' => '',
        'suffix' => '',
      ),
    );
    foreach ($fields as $key => $label) {
      $settings[$key] = array(
        'factor' => 1,
        'min' => '',
        'max' => '',
        'prefix' => '',
        'suffix' => '',
      );
    }
    return $settings;
  }

  /**
   * @inheritdoc
   */
  protected static function _propertyDefinitions(FieldStorageDefinitionInterface $field_definition, $fields) {
    $properties = array();
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Value'))
      ->setRequired(TRUE);
    foreach ($fields as $key => $label) {
      $properties[$key] = DataDefinition::create('string')
        ->setLabel($label)
        ->setRequired(TRUE);
    }
    return $properties;
  }

  /**
   * @inheritdoc
   */
  protected static function _schema(FieldStorageDefinitionInterface $field_definition, $fields) {
    $settings = $field_definition->getSetting('storage_value');
    $schema = array(
      'columns' => array(
        'value' => array(
          'type' => 'numeric',
          'precision' => $settings['precision'],
          'scale' => $settings['scale'],
        )
      ),
    );
    foreach ($fields as $key => $label) {
      $settings = $field_definition->getSetting('storage_' . $key);
      $schema['columns'][$key] = array(
        'type' => 'numeric',
        'precision' => $settings['precision'],
        'scale' => $settings['scale'],
      );
    }

    return $schema;
  }

  private function _storageSettings(&$element, $key, $label, $has_data, $settings) {
    $element[$key] = array(
      '#type' => 'fieldset',
      '#title' => $label,
    );
    $range = range(10, 32);
    $element[$key]['precision'] = array(
      '#type' => 'select',
      '#title' => t('Precision'),
      '#options' => array_combine($range, $range),
      '#default_value' => $settings['precision'],
      '#description' => t('The total number of digits to store in the database, including those to the right of the decimal.'),
      '#disabled' => $has_data,
    );
    $range = range(0, 10);
    $element[$key]['scale'] = array(
      '#type' => 'select',
      '#title' => t('Scale', array(), array('decimal places')),
      '#options' => array_combine($range, $range),
      '#default_value' => $settings['scale'],
      '#description' => t('The number of digits to the right of the decimal.'),
      '#disabled' => $has_data,
    );
  }

  /**
   * @inheritdoc
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = array();

    foreach ($this->fields() as $key => $label) {
      $this->_storageSettings($element, $key, $label, $has_data, $this->getSetting('storage_' . $key));
    }
    $this->_storageSettings($element, 'value', t('Full dimension'), $has_data, $this->getSetting('storage_value'));

    return $element;
  }

  private function _fieldSettings(&$element, $key, $label, $hide_constraints = FALSE) {
    $settings = $this->getSetting($key);
    $storage_settings = $this->getSetting('storage_' . $key);

    $element[$key] = array(
      '#type' => 'fieldset',
      '#title' => $label,
    );
    $element[$key]['factor'] = array(
      '#type' => 'number',
      '#title' => t('Factor'),
      '#default_value' => $settings['factor'],
      '#step' => pow(0.1, 2),
      '#required' => TRUE,
      '#description' => t('A factor to multiply the @label with when calculating the @field', array('@label' => $label, '@field' => $this->getFieldDefinition()->getLabel())),
      '#access' => !$hide_constraints,
    );
    $element[$key]['min'] = array(
      '#type' => 'number',
      '#title' => t('Minimum'),
      '#default_value' => $settings['min'],
      '#step' => pow(0.1, $storage_settings['scale']),
      '#description' => t('The minimum value that should be allowed in this field. Leave blank for no minimum.'),
      '#access' => !$hide_constraints,
    );
    $element[$key]['max'] = array(
      '#type' => 'number',
      '#title' => t('Maximum'),
      '#default_value' => $settings['max'],
      '#step' => pow(0.1, $storage_settings['scale']),
      '#description' => t('The maximum value that should be allowed in this field. Leave blank for no maximum.'),
      '#access' => !$hide_constraints,
    );
    $element[$key]['prefix'] = array(
      '#type' => 'textfield',
      '#title' => t('Prefix'),
      '#default_value' => $settings['prefix'],
      '#size' => 60,
      '#description' => t("Define a string that should be prefixed to the value, like 'cm ' or 'inch '. Leave blank for none. Separate singular and plural values with a pipe ('inch|inches')."),
    );
    $element[$key]['suffix'] = array(
      '#type' => 'textfield',
      '#title' => t('Suffix'),
      '#default_value' => $settings['suffix'],
      '#size' => 60,
      '#description' => t("Define a string that should be suffixed to the value, like ' mm', ' inch'. Leave blank for none. Separate singular and plural values with a pipe ('inch|inches')."),
    );

  }

  /**
   * @inheritdoc
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = array();

    foreach ($this->fields() as $key => $label) {
      $this->_fieldSettings($element, $key, $label);
    }

    $this->_fieldSettings($element, 'value', t('Full dimension'), TRUE);

    return $element;
  }

  /**
   * @inheritdoc
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = array();
    foreach ($this->definition->getConstraints() as $name => $options) {
      $constraints[] = $constraint_manager->create($name, $options);
    }

    foreach ($this->fields() as $key => $label) {
      $settings = $this->getSetting($key);
      $constraints[] = $constraint_manager->create('ComplexData', array(
        $key => array(
          'Regex' => array(
            'pattern' => '/^[+-]?((\d+(\.\d*)?)|(\.\d+))$/i',
          )
        ),
      ));
      if (!empty($settings['min'])) {
        $min = $settings['min'];
        $constraints[] = $constraint_manager->create('ComplexData', array(
          $key => array(
            'Range' => array(
              'min' => $min,
              'minMessage' => t('%name: the value may be no less than %min.', array('%name' => $label, '%min' => $min)),
            )
          ),
        ));
      }

      if (!empty($settings['max'])) {
        $max = $settings['max'];
        $constraints[] = $constraint_manager->create('ComplexData', array(
          $key => array(
            'Range' => array(
              'max' => $max,
              'maxMessage' => t('%name: the value may be no greater than %max.', array('%name' => $label, '%max' => $max)),
            )
          ),
        ));
      }
    }

    return $constraints;
  }

  /**
   * @inheritdoc
   */
  public function preSave() {
    $values = array();
    foreach ($this->fields() as $key => $label) {
      $values[$key] = $this->{$key};
      $storage_settings = $this->getSetting('storage_' . $key);
      $this->{$key} = round($this->{$key}, $storage_settings['scale']);
    }
    $this->value = $this->calculate($values);
  }

  /**
   * @inheritdoc
   */
  public function isEmpty() {
    foreach ($this->fields() as $key => $label) {
      if (empty($this->{$key}) && (string) $this->{$key} !== '0') {
        return TRUE;
      }
    }
    return FALSE;
  }

  public function calculate($values) {
    $value = 1;
    foreach ($this->fields() as $key => $label) {
      $settings = $this->getSetting($key);
      $storage_settings = $this->getSetting('storage_' . $key);
      $values[$key] = round($values[$key], $storage_settings['scale']);
      $value *= $values[$key] * $settings['factor'];
    }
    $storage_settings = $this->getSetting('storage_value');
    return round($value, $storage_settings['scale']);
  }

}
