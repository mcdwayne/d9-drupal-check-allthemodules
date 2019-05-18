<?php

namespace Drupal\flexfield\Plugin\FlexFieldType;

use Drupal\flexfield\Plugin\FlexFieldTypeBase;
use Drupal\flexfield\Plugin\Field\FieldType\FlexItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'text' flexfield type.
 *
 * Simple textfield flexfield widget. Value renders as it is entered by the
 * user.
 *
 * @FlexFieldType(
 *   id = "select",
 *   label = @Translation("Select list"),
 *   description = @Translation("")
 * )
 */
class Select extends FlexFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultWidgetSettings() {
    return [
      'allowed_values' => [],
    ] + parent::defaultWidgetSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFormatterSettings() {
    return [
      'render' => 'value',
    ] + parent::defaultFormatterSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);

    // Only give the empty option when it's not required, or if it's required
    // but we don't have a value yet (forcing the user to make a selection).
    if (!$this->widget_settings['required'] || !isset($items[$delta]->{$this->name})) {
      $options = ['' => '- Select -'] + $this->widget_settings['allowed_values'];
    }
    else {
      $options = $this->widget_settings['allowed_values'];
    }

    // Add our widget type and additional properties and return.
    return [
      '#type' => 'select',
      '#options' => $options,
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state) {

    $element = parent::widgetSettingsForm($form, $form_state);

    // Some table columns containing raw markup.
    $element['allowed_values'] = [
      '#type' => 'textarea',
      '#title' => t('Allowed Values'),
      '#rows' => 4,
      '#default_value' => $this->allowedValuesString($this->widget_settings['allowed_values']),
      '#element_validate' => [[get_class($this), 'validateAllowedValues']],
      // Custom attribute to communicate the max length to our validation handler
      '#column_max_length' => $this->max_length,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formatterSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::formatterSettingsForm($form, $form_state);

    $form['render'] = [
      '#type' => 'select',
      '#title' => t('Output'),
      '#options' => [
        'value' => t('Value'),
        'key' => t('Key'),
      ],
      '#default_value' => $this->formatter_settings['render'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function value(FlexItem $item) {
    if ($this->formatter_settings['render'] == 'key') {
      parent::value($item);
    }
    else {
      return isset($this->widget_settings['allowed_values'][$item->{$this->name}]) ? $this->widget_settings['allowed_values'][$item->{$this->name}] : '';
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
  public function allowedValuesString($values) {
    $lines = [];
    foreach ($values as $key => $value) {
      $lines[] = "$key|$value";
    }
    return implode("\n", $lines);
  }

  /**
   * Extracts the allowed values array from the allowed_values element.
   *
   * Adapted from \Drupal\options\Plugin\Field\FieldType\ListItemBase
   *
   * @param string $string
   *   The raw string to extract values from.
   *
   * @return array|null
   *   The array of extracted key/value pairs, or NULL if the string is invalid.
   */
  protected static function extractAllowedValues($string) {
    $values = [];

    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    foreach ($list as $position => $text) {
      // Check for an explicit key.
      $matches = [];
      if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $key = trim($matches[1]);
        $value = trim($matches[2]);
      }
      // Otherwise see if we can use the value as the key.
      else {
        $key = $value = $text;
      }

      $values[$key] = $value;
    }

    return $values;
  }

  /**
   * #element_validate callback for select field allowed values.
   *
   * @param $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param $form_state
   *   The current state of the form for the form this element belongs to.
   *
   * @see \Drupal\Core\Render\Element\FormElement::processPattern()
   */
  public static function validateAllowedValues($element, FormStateInterface $form_state) {
    $values = static::extractAllowedValues($element['#value']);

    if (!is_array($values)) {
      $form_state->setError($element, t('Allowed values list: invalid input.'));
    }
    else {
      // Check that keys are valid for the field type.
      foreach ($values as $key => $value) {
        if (strlen($key) > $element['#column_max_length']) {
          $form_state->setError($element, t('Allowed value key must be less than @max_length characters', ['@max_length' => $element['#column_max_length']]));
          break;
        }
      }
      // ???
      $form_state->setValueForElement($element, $values);
    }
  }

}
