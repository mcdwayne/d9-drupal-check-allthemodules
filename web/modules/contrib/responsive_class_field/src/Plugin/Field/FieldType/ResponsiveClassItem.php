<?php

namespace Drupal\responsive_class_field\Plugin\Field\FieldType;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Field item of type 'responsive_class'.
 *
 * @FieldType(
 *   id = "responsive_class",
 *   label = @Translation("Responsive class"),
 *   module = "responsive_class_field",
 *   default_widget = "responsive_class_widget",
 *   default_formatter = "responsive_class_formatter",
 *   cardinality = 1,
 *   translatable = FALSE,
 * )
 */
class ResponsiveClassItem extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $responsive_class = \Drupal::service('responsive_class_field');

    return [
      'pattern' => '{breakpoint}-{value}',
      'empty_class' => FALSE,
      'breakpoint_group' => $responsive_class->getDefaultBreakpointGroup(),
      'breakpoints' => $responsive_class->getDefaultBreakpoints(),
      'empty_value' => '_none',
      'allowed_values' => [],
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $responsive_class = \Drupal::service('responsive_class_field');

    $element = [
      '#tree' => TRUE,
    ];

    // The pattern for the generated CSS classes.
    $element['pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class pattern'),
      '#description' => $this->t("The pattern for generated CSS classes. Supports the <em>{breakpoint}</em> pattern that will be replaced by the breakpoint pattern value, and the <em>{value}</em> pattern, that will be replaced by the selected value's key."),
      '#default_value' => $this->getSetting('pattern'),
    ];

    // The pattern for the generated CSS classes.
    $element['empty_class'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Provide an empty class'),
      '#description' => $this->t("If checked and the field is empty, a CSS class with empty values for the <em>{breakpoint}</em> and <em>{value}</em> patterns will be generated."),
      '#default_value' => !empty($this->getSetting('empty_class')),
    ];

    // The breakpoint group to use.
    $breakpoint_group = $this->getSetting('breakpoint_group') ?: 'responsive_class_field';
    $element['breakpoint_group'] = [
      '#type' => 'select',
      '#title' => $this->t('Breakpoint group'),
      '#default_value' => $breakpoint_group,
      '#options' => $responsive_class->getBreakpointGroups(),
      '#required' => TRUE,
      '#description' => $this->t('Select the breakpoint group to use for this field.'),
      '#ajax' => [
        'callback' => [$this, 'breakpointMappingFormAjax'],
        'wrapper' => 'responsive-class-breakpoints-wrapper',
      ],
    ];

    // The configured breakpoints.
    $element += $responsive_class->buildBreakpointsSettingsForm(
      [],
      $form_state,
      $form_state->getValue(['settings', 'breakpoint_group']) ?: $breakpoint_group,
      $this->getSetting('breakpoints'),
      'settings'
    );

    // The empty value for the select options.
    $element['empty_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Empty value'),
      '#description' => $this->t('The empty value key for the "---" option (no selection). This key must not be used within the allowed values list. Defaults to <em>_none</em>'),
      '#default_value' => $this->getSetting('empty_value'),
    ];

    $allowed_values = $this->getSetting('allowed_values');
    $element['allowed_values'] = [
      '#type' => 'textarea',
      '#title' => t('Allowed values list'),
      '#description' => $this->t('The possible values that can be selected for each breakpoint. Enter one value per line, in the format key|label. The key is the value that will be used to replace the <em>{value}</em> token within the class pattern. The label will be used in edit forms.'),
      '#default_value' => $this->allowedValuesString($allowed_values),
      '#rows' => 10,
      '#element_validate' => [[get_class($this), 'validateAllowedValues']],
      '#field_has_data' => $has_data,
      '#field_name' => $this->getFieldDefinition()->getName(),
      '#entity_type' => $this->getEntity()->getEntityTypeId(),
      '#allowed_values' => $allowed_values,
    ];

    $element += parent::storageSettingsForm($form, $form_state, $has_data);

    return $element;
  }

  /**
   * Get the form for mapping breakpoints to breakpoint replacements.
   */
  public function breakpointMappingFormAjax($form, FormStateInterface $form_state) {
    return $form['settings']['breakpoints'];
  }

  /**
   * {@inheritdoc}
   */
  public static function storageSettingsToConfigData(array $settings) {
    $settings = parent::storageSettingsToConfigData($settings);

    // Process breakpoints.
    if (isset($settings['breakpoints'])) {
      $configured_breakpoints = $settings['breakpoints'];
      $breakpoints = [];
      foreach ($configured_breakpoints as $key => $value) {
        if (empty($value['enabled'])) {
          continue;
        }

        $breakpoint = $value;
        unset($breakpoint['enabled']);
        $breakpoint['breakpoint'] = $key;

        $breakpoints[] = $breakpoint;
      }
      $settings['breakpoints'] = $breakpoints;
    }

    // Process allowed values.
    if (isset($settings['allowed_values'])) {
      $settings['allowed_values'] = static::structureAllowedValues($settings['allowed_values']);
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function storageSettingsFromConfigData(array $settings) {
    $settings = parent::storageSettingsFromConfigData($settings);

    // Process breakpoints.
    if (isset($settings['breakpoints'])) {
      $configured_breakpoints = $settings['breakpoints'];
      $breakpoints = [];
      foreach ($configured_breakpoints as $value) {
        $breakpoint = $value;
        unset($breakpoint['breakpoint']);
        $breakpoint['enabled'] = TRUE;

        $breakpoints[$value['breakpoint']] = $breakpoint;
      }
      $settings['breakpoints'] = $breakpoints;
    }

    // Process allowed values.
    if (isset($settings['allowed_values'])) {
      $settings['allowed_values'] = static::simplifyAllowedValues($settings['allowed_values']);
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'description' => 'The selected responsive styles.',
          'type' => 'blob',
          'not null' => TRUE,
          'serialize' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['value'] = DataDefinition::create('responsive_class')
      ->setLabel(new TranslatableMarkup('Responsive class settings'))
      ->setRequired(FALSE);;

    $properties['classes'] = DataDefinition::create('responsive_class_classes')
      ->setLabel(new TranslatableMarkup('Generated responsive classes'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\responsive_class_field\Plugin\DataType\ResponsiveClassClassesData')
      ->setInternal(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return empty($value) || $value === serialize([]);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions() {
    return $this->getSettableOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues() {
    return array_keys($this->getSettableOptions());
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions() {
    return $this->getSetting('allowed_values');
  }

  /**
   * Validate callback for options field allowed values.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form for the form this element belongs to.
   *
   * @see FormElement::processPattern()
   */
  public static function validateAllowedValues(array $element, FormStateInterface $form_state) {
    $values = static::extractAllowedValues($element['#value'], $element['#field_has_data']);

    if (!is_array($values)) {
      $form_state->setError($element, t('Allowed values list: invalid input.'));
    }
    else {
      // Check that keys are valid for the field type.
      foreach ($values as $key => $value) {
        if ($error = static::validateAllowedValue($key)) {
          $form_state->setError($element, $error);
          break;
        }
      }

      $form_state->setValueForElement($element, $values);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected static function validateAllowedValue($option) {
    if (Unicode::strlen($option) > 50) {
      return $this->t('Allowed values list: each key must be a string at most 50 characters long.');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected static function castAllowedValue($value) {
    return (string) $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (isset($values['value'])) {
      // Single serialized values on shared tables for base fields are not
      // always unserialized. https://www.drupal.org/node/2788637
      if (is_string($values['value'])) {
        $values['value'] = unserialize($values['value']);
      }

      // Only include values that differ from the empty_value.
      $breakpoints_to_save = $this->getNonEmptyValues($values['value']);

      $values['value'] = $breakpoints_to_save;
      $values['classes'] = $this->generateClasses($breakpoints_to_save);
    }
    elseif (!empty($classes = $this->generateClasses([]))) {
      $values['classes'] = $classes;
    }

    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    // Get the value about to be saved.
    $current_value = $this->value;

    // Only unserialize if still serialized string.
    if (is_string($current_value)) {
      $current_value = unserialize($current_value);
    }

    // Update the value to only save non-empty breakpoints.
    $this->value = $this->getNonEmptyValues($current_value);
  }

  /**
   * Remove empty values from the given values array.
   *
   * @param array $values
   *   Values array that may contain empty values.
   *
   * @return array
   *   Values array without empty values.
   */
  protected function getNonEmptyValues(array $values) {
    $values_to_save = [];

    $empty_value = $this->getSetting('empty_value');
    $breakpoints = $this->getSetting('breakpoints');

    foreach ($values as $value) {
      // Only include values that differ from the empty_value.
      if (!isset($breakpoints[$value['breakpoint_id']]) || $value['value'] == $empty_value) {
        continue;
      }
      $values_to_save[] = [
        'breakpoint_id' => $value['breakpoint_id'],
        'breakpoint' => $breakpoints[$value['breakpoint_id']]['token'],
        'value' => $value['value'],
      ];
    }

    return $values_to_save;
  }

  /**
   * Generate and return responsive classes.
   *
   * Uses the 'pattern' field storage setting to generate a class for every
   * breakpoint and return all classes as array. The pattern may contain
   * the placeholders {breakpoint} and {value} that will be replaced by the
   * configured breakpoint values.
   *
   * @param array $breakpoints
   *   Array of breakpoint values, whereas every breakpoint value is an
   *   associative array with the following keys:
   *   - breakpoint_id: ID of the breakpoint in the breakpoints definition.
   *   - breakpoint: Replacement value for the {breakpoint} token.
   *   - value: Replacement value for the {value} token.
   *
   * @return array
   *   Array of generated classes.
   */
  protected function generateClasses(array $breakpoints) {
    $pattern = $this->getSetting('pattern');
    $classes = [];

    // Generate a class for each breakpoint.
    foreach ($breakpoints as $value) {
      $class = str_replace('{breakpoint}', $value['breakpoint'], $pattern);
      $class = str_replace('{value}', $value['value'], $class);
      $class = Html::getClass($class);
      $classes[] = $class;
    }

    // Whether to generate an empty class.
    if (empty($breakpoints) && !empty($this->getSetting('empty_class'))) {
      $class = str_replace('{breakpoint}', '', $pattern);
      $class = str_replace('{value}', '', $class);
      $class = Html::getClass($class);
      $classes[] = $class;
    }

    return $classes;
  }

  /**
   * Extracts the allowed values array from the allowed_values element.
   *
   * @param string $string
   *   The raw string to extract values from.
   * @param bool $has_data
   *   The current field already has data inserted or not.
   *
   * @return array|null
   *   The array of extracted key/value pairs, or NULL if the string is invalid.
   *
   * @see ListItemBase::allowedValuesString()
   */
  protected static function extractAllowedValues($string, $has_data) {
    $values = [];

    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    $generated_keys = $explicit_keys = FALSE;
    foreach ($list as $position => $text) {
      // Check for an explicit key.
      $matches = [];
      if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $key = trim($matches[1]);
        $value = trim($matches[2]);
        $explicit_keys = TRUE;
      }
      // Otherwise see if we can use the value as the key.
      elseif (!static::validateAllowedValue($text)) {
        $key = $value = $text;
        $explicit_keys = TRUE;
      }
      // Otherwise see if we can generate a key from the position.
      elseif (!$has_data) {
        $key = (string) $position;
        $value = $text;
        $generated_keys = TRUE;
      }
      else {
        return;
      }

      $values[$key] = $value;
    }

    // We generate keys only if the list contains no explicit key at all.
    if ($explicit_keys && $generated_keys) {
      return;
    }

    return $values;
  }

  /**
   * Generates a string representation of an array of 'allowed values'.
   *
   * This string format is suitable for edition in a textarea.
   *
   * @param array $values
   *   An array of values, where array keys are values and array values are
   *   labels.
   *
   * @return string
   *   The string representation of the $values array:
   *    - Values are separated by a carriage return.
   *    - Each value is in the format "value|label" or "value".
   */
  protected function allowedValuesString(array $values) {
    $lines = [];
    foreach ($values as $key => $value) {
      $lines[] = "$key|$value";
    }
    return implode("\n", $lines);
  }

  /**
   * Simplifies allowed values to a key-value array from the structured array.
   *
   * @param array $structured_values
   *   Array of items with a 'value' and 'label' key each for the allowed
   *   values.
   *
   * @return array
   *   Allowed values were the array key is the 'value' value, the value is
   *   the 'label' value.
   *
   * @see ListItemBase::structureAllowedValues()
   */
  protected static function simplifyAllowedValues(array $structured_values) {
    $values = [];
    foreach ($structured_values as $item) {
      if (is_array($item['label'])) {
        // Nested elements are embedded in the label.
        $item['label'] = static::simplifyAllowedValues($item['label']);
      }
      $values[$item['value']] = $item['label'];
    }
    return $values;
  }

  /**
   * Creates a structured array of allowed values from a key-value array.
   *
   * @param array $values
   *   Allowed values were the array key is the 'value' value, the value is
   *   the 'label' value.
   *
   * @return array
   *   Array of items with a 'value' and 'label' key each for the allowed
   *   values.
   *
   * @see ListItemBase::simplifyAllowedValues()
   */
  protected static function structureAllowedValues(array $values) {
    $structured_values = [];
    foreach ($values as $value => $label) {
      if (is_array($label)) {
        $label = static::structureAllowedValues($label);
      }
      $structured_values[] = [
        'value' => static::castAllowedValue($value),
        'label' => $label,
      ];
    }
    return $structured_values;
  }

}
