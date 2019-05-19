<?php

namespace Drupal\sliderwidget\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Number as NumberUtility;
use Drupal\Component\Utility\NestedArray;

/**
 * Provides a form element for numeric input, with special numeric validation.
 *
 * Properties:
 * - #default_value: A valid floating point number.
 * - #animate: Disables the slider if set to true.
 * - #disabled: The maximum value of the slider.
 * - #min: Minimum value.
 * - #max: Maximum value.
 * - #step: Ensures that the number is an even multiple of step, offset by #min
 *   if specified. A #min of 1 and a #step of 2 would allow values of 1, 3, 5,
 *   etc.
 * - #orientation: Determines whether the slider handles move horizontally
 *   (min on left, max on right) or vertcally (min on bottom, max on top).
 *   Possible values: "horizontal", "vertical".
 * - #range: Whether the slider represents a range.
 * - #slider_style: Some default color styles for ease of use red, green, blue.
 * - #size: The size of the input element in characters.
 * - #display_inputs: If set to FALSE will display inputs only
 *   when javascript is disabled.
 * - #display_values: If enabled display the current
 *   values of slider as simple text.
 * - #display_values_format: Format of the displayed values
 *   The usage is mostly for showing $,% or other signs near the value.
 * - #display_bubble: Display a hint/bubble near each
 *   slider handle showing the value of that handle.
 * - #display_bubble_format: Format of the displaied values in bubble/hint.
 *   The usage is mostly for showing $,% or other signs near the value.
 *   Use %{value}% as slider value for range slider it can have two values
 *   separated by || like "$%{value}%MIN||$%{value}%MAX".
 * - #slider_length: Acceptable types are the same as css with and height
 *   and it will be used as width or height depending on #orientation.
 * - #display_inside_fieldset: Display the element inside a fieldset.
 * - #group: Sliders with the same group will be linked.
 *   The behavior of linked sliders depends on group_type parameter
 *   group name can only contain letters, numbers and underscore.
 * - #group_type: same : All sliders in the same group will have the same value.
 *   lock : All sliders in the same group will move with the exact same amount
 *   total : The total value of all sliders will be between min/max,
 *   increasing value of one slider decreases
 *   the rest of the sliders in the same group.
 * - #group_master: When set to TRUE, other sliders in the same
 *   group will change only if this slider changes
 *   values : TRUE , FALSE.
 * - #validate_range: Disable buildin range validation
 *   useful when element is used as widget
 *   for fields, since integer fields have range validation
 *   values : TRUE , FALSE
 * - #fields_to_sync_css_selector: In case there are other fields
 *   that should be sync dynamically when
 *   the slider changes
 *   value : .my_field_css_class
 * - #display_ignore_buton: When field is not required, and
 *   display_inputs option is inactive
 *   a checkbox will be shown allowing user to ignore the field
 *   and enter no value values : TRUE , FALSE.
 * - #hide_slider_handle_when_no_value: When the slider does not
 *   have any value by enabling this option it won't show the
 *   the slider handle unless user clicks on the slider to select a value
 *   values : TRUE , FALSE.
 *
 * Usage example:
 * @code
 * $form['quantity'] = array(
 *   '#type' => 'slider',
 *   '#title' => $this->t('Quantity'),
 *   '#input_title' => $this->t('Max'),
 *   '#animate' => 'fast',
 *   '#disabled' => FALSE,
 *   '#max' => 100,
 *   '#min' => 0,
 *   '#step' => 1,
 *   '#orientation' => 'horizontal',
 *   '#range' => FALSE,
 *   '#default_value' => NULL,
 *   '#slider_style' => NULL,
 *   '#size' => 3,
 *   '#display_inputs' => TRUE,
 *   '#display_values' => FALSE,
 *   '#display_values_format' => '%{value}%',
 *   '#display_bubble' => FALSE,
 *   '#display_bubble_format' => '%{value}%',
 *   '#slider_length' => NULL,
 *   '#display_inside_fieldset' => FALSE,
 *   '#group' => NULL,
 *   '#group_type' => 'same',
 *   '#group_master' => FALSE,
 *   '#validate_range' => TRUE,
 *   '#fields_to_sync_css_selector' => NULL,
 *   '#hide_slider_handle_when_no_value' => FALSE,
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Range
 * @see \Drupal\Core\Render\Element\Textfield
 *
 * @FormElement("slider")
 */
class Slider extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#title' => NULL,
      '#input_title' => $this->t('Max'),
      '#animate' => 'fast',
      '#disabled' => FALSE,
      '#max' => 100,
      '#min' => 0,
      '#step' => 1,
      '#orientation' => 'horizontal',
      '#range' => FALSE,
      '#default_value' => NULL,
      '#slider_style' => NULL,
      '#size' => 3,
      '#display_inputs' => TRUE,
      '#display_values' => FALSE,
      '#display_values_format' => '%{value}%',
      '#display_bubble' => FALSE,
      '#display_bubble_format' => '%{value}%',
      '#slider_length' => NULL,
      '#display_inside_fieldset' => FALSE,
      '#group' => NULL,
      '#group_type' => 'same',
      '#group_master' => FALSE,
      '#validate_range' => TRUE,
      '#fields_to_sync_css_selector' => NULL,
      '#hide_slider_handle_when_no_value' => FALSE,
      '#process' => [
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateSlider'],
      ],
      '#pre_render' => [
        [$class, 'preRenderSlider'],
      ],
      '#theme' => 'slider_sliderelement',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Form element validation handler for #type 'number'.
   *
   * Note that #required is validated by _form_validate() already.
   */
  public static function validateSlider(&$element, FormStateInterface $form_state, &$complete_form) {
    $values = $element['#value'];

    if ($values === '') {
      return;
    }

    $name = empty($element['#title']) ? $element['#parents'][0] : $element['#title'];

    if (!is_array($values)) {
      $values = [$values];
    }

    foreach ($values as $value) {
      // Ensure the input is numeric.
      if (!is_numeric($value)) {
        $form_state->setError($element, self::t('%name must be a number.', ['%name' => $name]));
        return;
      }

      // Ensure that the input is greater than the #min property, if set.
      if (isset($element['#min']) && $value < $element['#min']) {
        $form_state->setError($element, self::t('%name must be higher than or equal to %min.', [
          '%name' => $name,
          '%min' => $element['#min'],
        ]));
      }

      // Ensure that the input is less than the #max property, if set.
      if (isset($element['#max']) && $value > $element['#max']) {
        $form_state->setError($element, self::t('%name must be lower than or equal to %max.', [
          '%name' => $name,
          '%max' => $element['#max'],
        ]));
      }

      if (isset($element['#step']) && strtolower($element['#step']) != 'any') {
        // Check that the input is an allowed multiple of #step
        // (offset by #min if #min is set).
        $offset = isset($element['#min']) ? $element['#min'] : 0.0;

        if (!NumberUtility::validStep($value, $element['#step'], $offset)) {
          $form_state->setError($element, self::t('%name is not a valid number.', ['%name' => $name]));
        }
      }
    }
  }

  /**
   * Prepares a #type 'number' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #min, #max, #placeholder,
   *   #required, #attributes, #step, #size.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderSlider(array $element) {
    $element['#tree'] = TRUE;

    if (is_array($element['#value'])) {
      $value = isset($element['#value']['value']) ? $element['#value']['value'] : NULL;
    }
    else {
      $value = $element['#value'];
    }

    if ($element['#display_inside_fieldset']) {
      $element['slider'] = [
        '#type' => 'fieldset',
        '#title' => $element['#title'],
      ];
    }
    elseif ($element['#title']) {
      $element['slider'] = [
        '#type' => 'container',
      ];
    }

    if (!is_null($value) && $value !== '') {
      if ($value < $element['#min']) {
        $value = $element['#min'];
      }
      if ($value > $element['#max']) {
        $value = $element['#max'];
      }
    }
    $values = [];
    $values[] = $value;

    $group_css = '';
    if ($element['#group']) {
      $group_css = 'slider-group-' . $element['#group'];

      if ($element['#group_master']) {
        $group_css .= ' slider-group-master';
      }
    }

    $_attributes_new = [
      'class' => [
        'sliderwidget-value-field',
      ],
    ];
    if (isset($element['#attributes']) && is_array($element['#attributes'])) {
      $_attributes_new = NestedArray::mergeDeepArray([$_attributes_new, $element['#attributes']], TRUE);
    }

    // Generate input for slider.
    if (empty($element['#multi_value'])) {
      $element['slider']['value'] = [
        '#tree' => TRUE,
        '#prefix' => '<div id="' . $element['#id'] . '" class="sliderwidget ' . $group_css . '">' . '<div class="sliderwidget-event-field-container">',
        '#suffix' => '</div>',
        '#type' => 'number',
        '#max' => $element['#max'] * 1,
        '#min' => $element['#min'] * 1,
        '#step' => $element['#step'] * 1,
        '#required' => $element['#required'],
        '#element_validate' => [
          'Drupal\sliderwidget\Element\Slider',
          'sliderValidatePositiveNumber',
        ],
        '#title' => $element['#input_title'],
        '#value' => $value,
        '#display_values' => $element['#display_values'],
        '#disabled' => $element['#disabled'],
        '#size' => $element['#size'],
        '#attributes' => $_attributes_new,
        '#id' => $element['#id'],
        '#name' => $element['#name'],
      ];
    }
    if (!empty($element['#multi_value'])) {
      $element['slider']['from'] = [
        '#tree' => TRUE,
        '#prefix' => '<div id="' . $element['#id'] . '" class="sliderwidget ' . $group_css . '">' . '<div class="sliderwidget-event-field-container field--widget-range-text-fields clearfix">',
        '#suffix' => '</div>',
        '#type' => 'number',
        '#max' => $element['#max'] * 1,
        '#min' => $element['#min'] * 1,
        '#step' => $element['#step'] * 1,
        '#required' => $element['#required'],
        '#element_validate' => [
          'Drupal\sliderwidget\Element\Slider',
          'sliderValidatePositiveNumber',
        ],
        '#title' => $element['#input_title'],
        '#disabled' => $element['#disabled'],
        '#display_values' => $element['#display_values'],
        '#value' => $element['#values'][0],
        '#size' => $element['#size'],
        '#attributes' => $_attributes_new,
        '#id' => $element['#id'],
        '#name' => $element['#name'],
      ];

      $_attributes_to = [
        'class' => [
          'sliderwidget-value2-field',
        ],
      ];
      if (isset($element['#attributes']) && is_array($element['#attributes'])) {
        $_attributes_to = NestedArray::mergeDeepArray([
          $_attributes_to,
          $element['#attributes'],
        ], TRUE);
      }

      // Generate input for slider.
      $element['slider']['to'] = [
        '#tree' => TRUE,
        '#prefix' => '<div class="sliderwidget-event-field-container field--widget-range-text-fields clearfix">',
        '#suffix' => '</div>',
        '#type' => 'number',
        '#max' => $element['#max'] * 1,
        '#min' => $element['#min'] * 1,
        '#step' => $element['#step'] * 1,
        '#required' => $element['#required'],
        '#element_validate' => [
          'Drupal\sliderwidget\Element\Slider',
          'sliderValidatePositiveNumber',
        ],
        '#title' => $element['#input_title'],
        '#disabled' => $element['#disabled'],
        '#display_values' => $element['#display_values'],
        '#value' => $element['#values'][1],
        '#size' => $element['#size'],
        '#attributes' => $_attributes_to,
        '#id' => $element['#id'],
        '#name' => $element['#name'],
      ];

    }

    // For Ajax compatibility.
    if (isset($element['#ajax'])) {
      $ajax = @$element['#ajax'];
      if (!isset($ajax['trigger_as']) && isset($element['#name'])) {
        $value = NULL;
        $ajax['trigger_as'] = [
          'name' => $element['#name'],
          'value' => $value,
        ];
      }
      if (!isset($ajax['event'])) {
        $ajax['event'] = 'change';
      }
      // Generate input for slider.
      $element['slider']['value']['#ajax'] = $ajax;
    }

    if (!empty($element['#display_values'])) {
      foreach ($element['#values'] as $key => $value) {
        $values[$key] = str_replace('%{value}%', $value, $element['#display_values_format']);
      }
      $element['slider']['values_text'] = [
        '#markup' => '<div class="sliderwidget-display-values-field">' . htmlentities(implode(' - ', $values)) . '</div>',
      ];
    }

    $style = NULL;
    if (!is_null($element['#slider_length'])) {
      if ($element['#orientation'] == 'horizontal') {
        $style = "width: {$element['#slider_length']}";
      }
      else {
        $style = "height: {$element['#slider_length']}";
      }
    }
    if ($element['#hide_slider_handle_when_no_value']) {
      $element['slider']['note'] = [
        '#type' => 'markup',
        '#markup' => '<div class="sliderwidget-selectvalue-description">' . self::t('Please click on any part of the slider to select a value') . '</div>',
      ];
    }

    // Create markup for slider container.
    $element['slider']['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'sliderwidget-container',
          $element['#slider_style'],
        ],
        'style' => $style,
      ],
      '#attached' => [
        'library' => [
          'sliderwidget/element.slider',
          'range/range.field-widget',
        ],
        'drupalSettings' => [
          'sliderwidget_' . $element['#id'] => [
            'animate' => $element['#animate'],
            'adjust_field_min_css_selector' => $element['#adjust_field_min'],
            'adjust_field_max_css_selector' => $element['#adjust_field_max'],
            'disabled' => $element['#disabled'],
            'max' => $element['#max'] * 1,
            'min' => $element['#min'] * 1,
            'orientation' => $element['#orientation'],
            'range' => $element['#range'],
            'step' => $element['#step'] * 1,
            'display_inputs' => $element['#display_inputs'],
            'display_values_format' => $element['#display_values_format'],
            'display_bubble' => $element['#display_bubble'],
            'display_bubble_format' => $element['#display_bubble_format'],
            'display_values' => $element['#display_values'],
            'multi_value' => $element['#multi_value'],
            'values' => $element['#values'],
            'group' => $element['#group'],
            'group_type' => $element['#group_type'],
            'group_master' => $element['#group_master'],
            'fields_to_sync_css_selector' => $element['#fields_to_sync_css_selector'],
            'hide_slider_handle_when_no_value' => $element['#hide_slider_handle_when_no_value'],
          ],
        ],
      ],
      '#markup' => '',
      '#suffix' => '</div>',
    ];

    $element['#process_called'] = TRUE;
    return $element;
  }

  /**
   * Validate slider steps to be positive.
   *
   * @param array $element
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function sliderValidatePositiveNumber(array $element, FormStateInterface $form_state) {
    $value = $form_state->getValue($element['#parents']);
    if (!is_numeric($value) && !is_float($value) && !empty($value)) {
      $form_state->setError($element, t('The value should be a valid number'));
    }
    elseif ($value < 0) {
      $form_state->setError($element, $this->t('The value should be a valid positive number'));
    }
  }

}
