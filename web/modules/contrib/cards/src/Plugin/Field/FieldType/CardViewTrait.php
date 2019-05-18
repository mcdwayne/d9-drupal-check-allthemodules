<?php


namespace Drupal\cards\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

trait CardViewTrait {

  /**
   * Extracts the allowed values array from the allowed_values element.
   *
   * @param string $string
   *   The raw string to extract values from.
   *
   * @return array|null
   *   The array of extracted key/value pairs, or NULL if the string is
   *   invalid.
   *
   * @see \Drupal\options\Plugin\Field\FieldType\ListItemBase::allowedValuesString()
   */
  protected static function extractAllowedValues($string) {
    $values = [];

    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    $generated_keys = $explicit_keys = FALSE;
    foreach ($list as $text) {
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
   * The #element_validate callback for options field allowed values.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form for the form this element belongs to.
   *
   * @see \Drupal\Core\Render\Element\FormElement::processPattern()
   */
  public static function validateAllowedValues(array $element, FormStateInterface $form_state) {
    $values = static::extractAllowedValues($element['#value']);

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
   * Checks whether a candidate allowed value is valid.
   *
   * @param string $option
   *   The option value entered by the user.
   */
  protected static function validateAllowedValue($option) {
    // @todo this should ensure all keys are valid css classnames.
  }



  /**
   * {@inheritdoc}
   */
  public function addCardFieldSettingsForm(array $form, FormStateInterface $form_state) {

    $settings = $this->getSetting('settings');

    $element = [];

    foreach (static::availableEntityTypes() as $key => $type) {

      $group_1 = isset($settings[$key]['group_1']) ? $settings[$key]['group_1'] : [];
      $group_2 = isset($settings[$key]['group_2']) ? $settings[$key]['group_2'] : [];
      $group_3 = isset($settings[$key]['group_3']) ? $settings[$key]['group_3'] : [];
      $group_4 = isset($settings[$key]['group_4']) ? $settings[$key]['group_4'] : [];
      $adhoc = isset($settings[$key]['adhoc']) ? $settings[$key]['adhoc'] : [];


      $element['settings'][$key]['group_1'] = [
        '#type' => 'textarea',
        '#title' => t('Allowed group 1 values'),
        '#default_value' => $this->allowedValuesString($group_1),
        '#description' => t('Enter one value per line, in the format key|label.'),
        '#element_validate' => [[get_class($this), 'validateAllowedValues']],
      ];
      $element['settings'][$key]['group_2'] = [
        '#type' => 'textarea',
        '#title' => t('Allowed group 2 values'),
        '#default_value' => $this->allowedValuesString($group_2),
        '#description' => t('Enter one value per line, in the format key|label.'),
        '#element_validate' => [[get_class($this), 'validateAllowedValues']],
      ];
      $element['settings'][$key]['group_3'] = [
        '#type' => 'textarea',
        '#title' => t('Allowed group 3 values'),
        '#default_value' => $this->allowedValuesString($group_3),
        '#description' => t('Enter one value per line, in the format key|label.'),
        '#element_validate' => [[get_class($this), 'validateAllowedValues']],
      ];
      $element['settings'][$key]['group_4'] = [
        '#type' => 'textarea',
        '#title' => t('Allowed group 4 values'),
        '#default_value' => $this->allowedValuesString($group_4),
        '#description' => t('Enter one value per line, in the format key|label.'),
        '#element_validate' => [[get_class($this), 'validateAllowedValues']],
      ];
      $element['settings'][$key]['adhoc'] = [
        '#type' => 'textarea',
        '#title' => t('Adhoc classes'),
        '#default_value' => $this->allowedValuesString($adhoc),
        '#description' => t('Enter one value per line, in the format key|label.'),
        '#element_validate' => [[get_class($this), 'validateAllowedValues']],
      ];
    }

    return $element;

  }

  /**
   * {@inheritdoc}
   */
  public static function addCardschema(FieldStorageDefinitionInterface $field_definition) {

    $schema['columns']['group_1'] = [
      'type' => 'text',
      'size' => 'tiny',
      'not null' => FALSE,
    ];
    $schema['columns']['group_2'] = [
      'type' => 'text',
      'size' => 'tiny',
      'not null' => FALSE,
    ];
    $schema['columns']['group_3'] = [
      'type' => 'text',
      'size' => 'tiny',
      'not null' => FALSE,
    ];
    $schema['columns']['group_4'] = [
      'type' => 'text',
      'size' => 'tiny',
      'not null' => FALSE,
    ];
    $schema['columns']['adhoc'] = [
      'type' => 'blob',
      'size' => 'big',
      'not null' => TRUE,
      'serialize' => TRUE,
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function addCardpropertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['group_1'] = DataDefinition::create('string')
      ->setLabel(t('Color Value'));

    $properties['group_2'] = DataDefinition::create('string')
      ->setLabel(t('Width Value'));

    $properties['group_3'] = DataDefinition::create('string')
      ->setLabel(t('Height Value'));

    $properties['group_4'] = DataDefinition::create('string')
      ->setLabel(t('Icon Value'));

    $properties['adhoc'] = MapDataDefinition::create()
      ->setLabel(t('Classes Value'));

    return $properties;
  }


}

