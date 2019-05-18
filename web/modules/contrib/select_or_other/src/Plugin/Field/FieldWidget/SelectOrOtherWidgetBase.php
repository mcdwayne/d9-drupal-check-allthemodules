<?php

/**
 * @file
 * Contains \Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase.
 */

namespace Drupal\select_or_other\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for the 'select_or_other_*' widgets.
 *
 * Field types willing to enable one or several of the widgets defined in
 * select_or_other.module (select, radios/checkboxes, on/off checkbox) need to
 * implement the AllowedValuesInterface to specify the list of options to
 * display in the widgets.
 *
 * @see \Drupal\Core\TypedData\AllowedValuesInterface
 */
abstract class SelectOrOtherWidgetBase extends WidgetBase {

  /**
   * Identifies a 'None' option.
   */
  const SELECT_OR_OTHER_EMPTY_NONE = 'options_none';

  /**
   * Identifies a 'Select a value' option.
   */
  const SELECT_OR_OTHER_EMPTY_SELECT = 'options_select';

  /**
   * @var string
   */
  private $has_value;

  /**
   * Helper method to determine the identifying column for the field.
   *
   * @return string
   *   The name of the column.
   */
  protected function getColumn() {
    static $property_names;

    if (empty($property_names)) {
      $property_names = $this->fieldDefinition->getFieldStorageDefinition()
        ->getPropertyNames();
    }

    return reset($property_names);
  }

  /**
   * Helper method to determine if the field supports multiple values.
   *
   * @return bool
   */
  protected function isMultiple() {
    return $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
  }

  /**
   * Helper method to determine if the field is required.
   * @return bool
   */
  protected function isRequired() {
    return $this->fieldDefinition->isRequired();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'select_element_type' => 'select_or_other_select',
      'available_options' => '',
      'other' => 'Other',
      'other_title' => '',
      'other_unknown_defaults' => 'other',
      'other_size' => 60,
      'sort_options' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['select_element_type'] = [
      '#title' => $this->t('Type of select form element'),
      '#type' => 'select',
      '#options' => $this->selectElementTypeOptions(),
      '#default_value' => $this->getSetting('select_element_type'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $options = $this->selectElementTypeOptions();
    $summary[] = $this->t('Type of select form element') . ': ' . $options[$this->getSetting('select_element_type')];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Prepare some properties for the child methods to build the actual form
    // element.
    $this->has_value = isset($items[0]->{$this->getColumn()});

    $element += [
      '#type' => $this->getSetting('select_element_type'),
      '#options' => $this->getOptions(),
      '#default_value' => $this->getSelectedOptions($items),
      // Do not display a 'multiple' select box if there is only one option.
      '#multiple' => $this->isMultiple() && count($this->getOptions()) > 1,
      '#key_column' => $this->getColumn(),
      '#element_validate' => [[get_class($this), 'validateElement']],
    ];

    // The rest of the $element is built by child method implementations.

    return $element;
  }

  /**
   *
   * Return whether $items of formElement method contains any data.
   *
   * @return bool
   */
  public function hasValue() {
    return $this->has_value;
  }

  /**
   * Form validation handler for widget elements.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    if ($element['#required'] && $element['#value'] == '_none') {
      $form_state->setError($element, t('@name field is required.', array('@name' => $element['#title'])));
    }

    // Massage submitted form values.
    // Drupal\Core\Field\WidgetBase::submit() expects values as
    // an array of values keyed by delta first, then by column, while our
    // widgets return the opposite.

    if (is_array($element['#value'])) {
      $values = array_values($element['#value']);
    }
    else {
      $values = array($element['#value']);
    }

    // Filter out the 'none' option. Use a strict comparison, because
    // 0 == 'any string'.
    $index = array_search('_none', $values, TRUE);
    if ($index !== FALSE) {
      unset($values[$index]);
    }

    // Transpose selections from field => delta to delta => field.
    $items = array();
    foreach ($values as $value) {
      $items[] = array($element['#key_column'] => $value);
    }
    $form_state->setValueForElement($element, $items);
  }

  /**
   * Returns the array of options for the widget.
   *
   * @return array
   *   The array of available options for the widget.
   */
  abstract protected function getOptions();

  /**
   * Determines selected options from the incoming field values.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values.
   *
   * @return array
   *   The array of corresponding selected options.
   */
  protected function getSelectedOptions(FieldItemListInterface $items) {
    $selected_options = [];

    foreach ($items as $item) {
      $selected_options[] = $item->{$this->getColumn()};
    }

    $selected_options = $this->prepareSelectedOptions($selected_options);

    if ($selected_options) {
      // We need to check against a flat list of options.
      $flattened_options = $this->flattenOptions($this->getOptions());

      foreach ($selected_options as $key => $selected_option) {
        // Remove the option if it does not exist in the options.
        if (!isset($flattened_options[$selected_option])) {
          unset($selected_options[$key]);
        }
      }
    }

    return $selected_options;
  }

  /**
   * Flattens an array of allowed values.
   *
   * @param array $array
   *   A single or multidimensional array.
   *
   * @return array
   *   The flattened array.
   */
  protected function flattenOptions(array $array) {
    $result = array();
    array_walk_recursive($array, function ($a, $b) use (&$result) {
      $result[$b] = $a;
    });
    return $result;
  }

  /**
   * Indicates whether the widgets support optgroups.
   *
   * @return bool
   *   TRUE if the widget supports optgroups, FALSE otherwise.
   */
  protected function supportsGroups() {
    return FALSE;
  }

  /**
   * Sanitizes a string label to display as an option.
   *
   * @param string $label
   *   The label to sanitize.
   */
  static protected function sanitizeLabel(&$label) {
    // Allow a limited set of HTML tags.
    $label = Xss::filter($label);
  }

  /**
   * Returns the empty option to add to the list of options, if any.
   *
   * @return string|null
   *   Either static::OPTIONS_EMPTY_NONE, static::OPTIONS_EMPTY_SELECT, or NULL.
   */
  protected function getEmptyOption() {
  }

  /**
   * Prepares selected options for comparison to the available options.
   *
   * Sometimes widgets have to change the keys of their available options. This
   * method allows those widgets to do the same with the selected options to
   * ensure they actually end up selected in the widget.
   *
   * @param array $options
   *   The options to prepare.
   *
   * @return array
   *   The prepared option.
   */
  protected function prepareSelectedOptions(array $options) {
    return $options;
  }

  /**
   * Returns the types of select elements available for selection.
   *
   * @return array
   *   The available select element types.
   *
   * @codeCoverageIgnore
   *   Testing this method would only test if this hard-coded array equals the
   *   one in the test case.
   */
  private function selectElementTypeOptions() {
    return [
      'select_or_other_select' => $this->t('Select list'),
      'select_or_other_buttons' => $this->t('Radiobuttons/checkboxes'),
    ];
  }

}
