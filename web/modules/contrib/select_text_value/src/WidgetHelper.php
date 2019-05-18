<?php

namespace Drupal\select_text_value;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class WidgetHelper.
 *
 * @package Drupal\select_text_value
 */
class WidgetHelper {

  use StringTranslationTrait;

  /**
   * Simple array of default values for all form widgets.
   *
   * @return array
   *    Shared settings across all widgets.
   */
  public static function defaultSettings() {
    return [
      'select_type' => 'select',
      'allowed_values' => '',
      'custom_value_label' => 'Other',
      'custom_value_field_title' => '',
      'custom_value_field_description' => '',
    ];
  }

  /**
   * Shared settings for across all widgets.
   *
   * @param array $form
   *   Widget settings form.
   * @param array $settings
   *   Widget settings.
   *
   * @return array
   *   Drupal settings form for this widget.
   */
  public function settingsForm(array $form, array $settings) {

    $form['select_type'] = [
      '#title' => $this->t('Select type'),
      '#type' => 'select',
      '#options' => [
        'select' => $this->t('Select'),
        'radios' => $this->t('Radio Buttons'),
      ],
      '#default_value' => $settings['select_type'],
      '#required' => TRUE,
    ];

    $form['custom_value_field_title'] = [
      '#title' => $this->t('Custom value field title'),
      '#description' => $this->t('The title for the field that is shown when the custom value is selected.'),
      '#type' => 'textfield',
      '#default_value' => $settings['custom_value_field_title'],
    ];

    $form['custom_value_field_description'] = [
      '#title' => $this->t('Custom value field description'),
      '#description' => $this->t('Help text for the field that is shown when the custom value is selected.<br />Allowed HTML tags: @tags', ['@tags' => FieldFilteredMarkup::displayAllowedTags()]),
      '#type' => 'textarea',
      '#default_value' => $settings['custom_value_field_description'],
    ];

    $form['allowed_values'] = [
      '#title' => $this->t('Allowed values list'),
      '#type' => 'textarea',
      '#description' => $this->allowedValuesDescription(),
      '#default_value' => $settings['allowed_values'],
      '#required' => TRUE,
    ];

    $form['custom_value_label'] = [
      '#title' => $this->t('Custom value label'),
      '#description' => $this->t('The value appended to the Allowed values list that, when selected, shows the original field.'),
      '#type' => 'textfield',
      '#default_value' => $settings['custom_value_label'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * Shared settings summary across all widgets.
   *
   * @param array $settings
   *   Settings from the widget.
   *
   * @return array
   *   Summary array.
   */
  public function settingsSummary(array $settings) {
    $summary = [];
    $summary[] = $this->t('Select type: @select_type', ['@select_type' => $settings['select_type']]);
    $summary[] = $this->t('Custom value filed title: @custom_value_field_title', ['@custom_value_field_title' => $settings['custom_value_field_title']]);
    $summary[] = $this->t('Allowed values:');
    $summary[] = $this->t('Custom value label: @custom_value_label', ['@custom_value_label' => $settings['custom_value_label']]);

    foreach (explode("\n", $settings['allowed_values']) as $value) {
      $summary[] = $value;
    }

    return $summary;
  }

  /**
   * Create a common 'select' field that all widgets use.
   *
   * @param array $settings
   *   Widget settings.
   * @param bool $required
   *   Whether or not the original field is required.
   *
   * @return array
   *   Render array for the new select field.
   */
  public function createSelectField(array $settings, $required) {
    $empty_option = $required ? $this->t('Select') : $this->t('None');

    return [
      '#type' => $settings['select_type'],
      '#required' => $required,
      '#empty_option' => "- {$empty_option} -",
      '#options' => $this->allowedValuesToArray($settings['allowed_values']) +
        ['_custom_value' => $settings['custom_value_label']],
    ];
  }

  /**
   * Move the original form element #required property to the select field.
   * The custom value field cannot easily be required from Drupal's perspective.
   *   @todo - Handle form #required this with custom validation on the form.
   *
   * @param array $element
   *   The widget's full render element.
   *
   * @return array
   *   The widget's altered render element.
   */
  public function handleFormElementRequired(array $element) {
    if (!empty($element['field']['#required'])) {
      $element['select']['#required'] = $element['field']['#required'];
      unset($element['field']['#required']);
    }
    return $element;
  }

  /**
   * Move the original form element title to the select field.
   *
   * @param array $element
   *   The widget's full render element.
   * @param array $settings
   *   The widget's settings.
   *
   * @return array
   *   The widget's altered render element.
   */
  public function setFormElementTitle(array $element, array $settings) {
    $element['select']['#title'] = $element['field']['#title'];
    unset($element['field']['#title']);

    if (!empty($settings['custom_value_field_title'])) {
      $element['field']['#title'] = $settings['custom_value_field_title'];
    }
    return $element;
  }

  /**
   * Move the original form element description to the select field.
   *
   * @param array $element
   *   The widget's full render element.
   * @param array $settings
   *   The widget's settings.
   *
   * @return array
   *   The widget's altered render element.
   */
  public function setFormElementDescription(array $element, array $settings) {
    $element['select']['#description'] = $element['field']['#description'];
    unset($element['field']['#description']);

    if (!empty($settings['custom_value_field_description'])) {
      $element['field']['#description'] = FieldFilteredMarkup::create($settings['custom_value_field_description']);
    }
    return $element;
  }

  /**
   * Set the default values when the form element is loaded.
   *
   * @param FieldItemListInterface $items
   *   Item values for the field.
   * @param int $delta
   *   Which item is being rendered.
   * @param array $element
   *   Render array element for the field.
   * @param array $settings
   *   Widget settings.
   *
   * @return array
   *   Altered element.
   */
  public function setFormElementDefaultValues(FieldItemListInterface $items, $delta, array $element, array $settings) {
    $allowed_values = $this->allowedValuesToArray($settings['allowed_values']);

    if (!empty($items[$delta]->value)) {
      $element['select']['#default_value'] = '_custom_value';

      if (in_array($items[$delta]->value, $allowed_values)) {
        $element['select']['#default_value'] = $items[$delta]->value;
        $element['field']['#default_value'] = '';
      }
    }

    return $element;
  }

  /**
   * Convert the stored allowed_values like string into an array.
   *
   * @param string $string
   *   "Allowed values"-like string.
   *
   * @return array
   *   Key->value pairs.
   */
  public function allowedValuesToArray($string) {
    $allowed_values = [];
    $lines = array_filter(explode("\n", $string));

    foreach ($lines as $line) {
      $line = trim($line);
      if (strpos($line, '|') !== FALSE) {
        list($key, $value) = explode('|', $line);
        $allowed_values[trim($key)] = trim($value);
      }
      else {
        $allowed_values[$line] = $line;
      }
    }

    return $allowed_values;
  }

  /**
   * Allowed values list field description.
   *
   * @return string
   *   HTML description for allowed values field.
   */
  public function allowedValuesDescription() {
    $description = '<p>' . t('The possible values this field can contain. Enter one value per line, in the format key|label.');
    $description .= '<br/>' . t('The key is the stored value, and must be numeric. The label will be used in displayed values and edit forms.');
    $description .= '<br/>' . t('The label is optional: if a line contains a single number, it will be used as key and label.');
    $description .= '<br/>' . t('Lists of labels are also accepted (one label per line), only if the field does not hold any values yet. Numeric keys will be automatically generated from the positions in the list.');
    $description .= '</p>';
    return $description;
  }

  /**
   * String fields only need to hide the value field itself.
   *
   * @param array $element
   *   Entire field element, consisting of multiple values.
   *
   * @return array
   *    Altered element with States.
   */
  public static function stringFieldAssignStates(array $element) {
    foreach (Element::children($element) as $delta) {
      if (!empty($element[$delta]['select'])) {
        $selector = $element[$delta]['select']['#name'];
        $element[$delta]['field']['#states'] = [
          'visible' => [
            ":input[name='{$selector}']" => ['value' => '_custom_value'],
          ],
          'required' => [
            ":input[name='{$selector}']" => ['value' => '_custom_value'],
          ],
        ];
      }
    }
    return $element;
  }

  /**
   * Formatted text fields need to hide both the textarea and the Format select.
   *
   * @param array $element
   *   Entire field element, consisting of multiple values.
   *
   * @return array
   *    Altered element with States.
   */
  public static function formattedTextFieldAssignStates(array $element) {
    foreach (Element::children($element) as $delta) {
      if (!empty($element[$delta]['select'])) {
        $selector = $element[$delta]['select']['#name'];
        $element[$delta]['field']['value']['#states'] = [
          'visible' => [
            ":input[name='{$selector}']" => ['value' => '_custom_value'],
          ],
          'required' => [
            ":input[name='{$selector}']" => ['value' => '_custom_value'],
          ],
        ];
        $element[$delta]['field']['format']['#states'] = [
          'visible' => [
            ":input[name='{$selector}']" => ['value' => '_custom_value'],
          ],
          'required' => [
            ":input[name='{$selector}']" => ['value' => '_custom_value'],
          ],
        ];
      }
    }
    return $element;
  }

  /**
   * Massage the user input values of a string field into place for storage.
   *
   * @param array $values
   *   Submitted user input.
   *
   * @return array
   *   Massaged values.
   */
  public function stringFieldMassageFormValues(array $values) {
    $new_values = [];

    foreach ($values as $value) {
      $new_value = [
        'value' => ($value['select'] == '_custom_value') ? $value['field'] : $value['select'],
        '_original_delta' => $value['_original_delta'],
      ];

      $new_values[] = $new_value;
    }
    return $new_values;
  }

  /**
   * Massage the user input values of a formatted field into place for storage.
   *
   * @param array $values
   *   Submitted user input.
   *
   * @return array
   *   Massaged values.
   */
  public function formattedTextFieldMassageFormValues(array $values) {
    $new_values = [];

    foreach ($values as $value) {
      $new_value = [
        'value' => ($value['select'] == '_custom_value') ? $value['field']['value'] : $value['select'],
        'format' => $value['field']['format'],
        '_original_delta' => $value['_original_delta'],
      ];

      $new_values[] = $new_value;
    }
    return $new_values;
  }

}
