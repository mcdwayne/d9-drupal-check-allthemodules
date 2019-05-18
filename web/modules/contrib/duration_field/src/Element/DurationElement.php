<?php

namespace Drupal\duration_field\Element;

use DateInterval;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;

/**
 * Defines the Duration form element.
 *
 * This form element takes a PHP DateInterval or an ISO 8601 duration string as
 * it's value, and returns a PHP DateInterval value when submitted.
 *
 * @FormElement("duration")
 */
class DurationElement extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#tree' => TRUE,
      // #granularity is a granularity string.
      // @see Drupal\duration_field\Plugin\DataType\GranularityStringdata
      '#granularity' => 'y:m:d:h:i:s',
      // #required_elements is a granularity string.
      // @see Drupal\duration_field\Plugin\DataType\GranularityStringdata
      '#required_elements' => '',
      '#element_validate' => [
        [$class, 'validateElement'],
      ],
      '#pre_render' => [
        [$class, 'preRenderElement'],
      ],
      '#process' => [
        'Drupal\Core\Render\Element\RenderElement::processAjaxForm',
        [$class, 'processElement'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {

    if ($input !== FALSE && !is_null($input)) {
      return \Drupal::service('duration_field.service')->convertDateArrayToDateInterval($input);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function processElement(&$element, FormStateInterface $form_state, &$complete_form) {

    $duration_service = \Drupal::service('duration_field.service');
    $granularity_service = \Drupal::service('duration_field.granularity.service');

    $value = FALSE;
    if (isset($element['#value']) && $element['#value']) {
      $value = $element['#value'];
    }
    elseif (isset($element['#default_value']) && $element['#default_value']) {
      $value = $element['#default_value'];
    }

    if (is_string($value) && !$duration_service->checkDurationInvalid($value)) {
      $value = new DateInterval($value);
    }

    if (!$value) {
      $value = $duration_service->createEmptyDateInterval();
    }

    // Create a wrapper for the elements. This is done in this manner
    // rather than nesting the elements in a wrapper with #prefix and #suffix
    // so as to not end up with [wrapper] as part of the name attribute
    // of the elements.
    $div = '<div';
    $classes = ['duration-inner-wrapper'];
    if (!empty($element['#states'])) {
      drupal_process_states($element);
    }
    foreach ($element['#attributes'] as $attribute => $attribute_value) {
      if (is_string($attribute_value)) {
        $div .= ' ' . $attribute . "='" . $attribute_value . "'";
      }
      elseif ($attribute == 'class') {
        $classes = array_merge($classes, $attribute_value);
      }
    }
    $div .= ' class="' . implode(' ', $classes) . '"';
    $div .= '>';

    // For reasons that unfortunately I don't remember at this time, all
    // elements of $element need to be at the same level, and cannot be nested.
    // as such, the opening div wrapper is created as #markup, with the closing
    // wrapper to come after the content.
    $element['wrapper_open'] = [
      '#markup' => $div,
      '#weight' => -1,
    ];

    $time_mappings = [
      'y' => t('Years'),
      'm' => t('Months'),
      'd' => t('Days'),
      'h' => t('Hours'),
      'i' => t('Minutes'),
      's' => t('Seconds'),
    ];

    foreach ($time_mappings as $key => $label) {
      // Only include elements that are part of the given granularity.
      if ($granularity_service->includeGranularityElement($key, $element['#granularity'])) {
        $element[$key] = [
          '#id' => $element['#id'] . '-' . $key,
          '#type' => 'number',
          '#title' => $label,
          // $value is a DateInterval object. This outputs the numeric value for
          // the key.
          '#default_value' => $value->format('%' . $key),
          // Only require elements that are part of the given require elements
          // granularity.
          '#required' => $granularity_service->includeGranularityElement($key, $element['#required_elements']),
          '#weight' => 0,
          '#min' => 0,
        ];

        // Apply the ajax of the main duration element to each granularity
        // input.
        if (!empty($element['#ajax'])) {
          $element[$info['key']]['#ajax'] = $element['#ajax'];
        }
      }
    }

    // The closing wrapper. See notes on the opening wrapper.
    $element['wrapper_close'] = [
      '#markup' => '</div>',
      '#weight' => 1,
    ];

    // Attach the CSS for the display of the output.
    $element['#attached']['library'][] = 'duration_field/element';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderElement(array $element) {

    // Set the wrapper as a container to the inner values.
    $element['#attributes']['type'] = 'container';

    Element::setAttributes($element, ['id', 'name', 'value']);
    static::setAttributes($element, ['form-duration']);

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * Converts the date array to a PHP DateInterval object, and sets the object
   * as the value of the form element. All handlers after this point will
   * receive the PHP DateInterval element as the value for this form element.
   */
  public static function validateElement(&$element, FormStateInterface $form_state, $form) {
    $date_array = $form_state->getValue($element['#parents']);
    $form_state->setValueForElement($element, \Drupal::service('duration_field.service')->convertDateArrayToDateInterval($date_array));
  }

}
