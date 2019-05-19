<?php

namespace Drupal\taarikh\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\Element\Datetime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for taarikh selection.
 *
 * Properties:
 * - #default_value: An array with the keys: 'year', 'month', and 'day'.
 *   Defaults to the current date if no value is supplied.
 * - #size: The size of the input element in characters.
 *
 * @code
 * $form['expiration'] = array(
 *   '#type' => 'taarikh_date',
 *   '#title' => $this->t('Content expiration'),
 *   '#default_value' => array('year' => 2020, 'month' => 2, 'day' => 15,)
 * );
 * @endcode
 *
 * @FormElement("taarikh_date")
 */
class TaarikhDate extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#element_validate' => [
        [$class, 'validateDate'],
      ],
      '#theme' => 'input__date',
      '#process' => [[$class, 'processDate']],
      '#pre_render' => [[$class, 'preRenderDate']],
      '#theme_wrappers' => ['form_element'],
      '#attributes' => ['type' => 'text'],
      '#date_date_format' => 'Y-m-d',
      '#date_year_range' => '1300:1450',
      '#date_first_day' => 0,
    ];
  }

  /**
   * Validation callback for a taarikh_date element.
   *
   * @param array $element
   *   The form element whose value is being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateDate(&$element, FormStateInterface $form_state, &$complete_form) {
    $input_exists = FALSE;
    $input = NestedArray::getValue($form_state->getValues(), $element['#parents'], $input_exists);
    if (!$input_exists) {
      return;
    }

    $title = !empty($element['#title']) ? $element['#title'] : '';
    $format = DateFormat::load('html_date')->getPattern();

    if (!empty($input)) {
      $taarikh_algorithm = !empty($element['#taarikh_algorithm']) ? $element['#taarikh_algorithm'] : 'fatimid_astronomical';
      // @todo: See if we can use a service container here.
      /** @var \Drupal\taarikh\TaarikhAlgorithmPluginInterface $algorithm */
      $algorithm = \Drupal::service('plugin.manager.taarikh_algorithm')->createInstance($taarikh_algorithm);

      try {
        $converted_date_formatted = $algorithm->convertToDrupalDateTime(
          $algorithm->constructDateFromFormat($input, $format)
        )->format($format);
        $form_state->setValueForElement($element, $converted_date_formatted);
      }
      catch (\Exception $ex) {
        $form_state->setError($element, t('The %field date is invalid. Please enter a date in the format %format.', ['%field' => $title, '%format' => Datetime::formatExample($format)]));
      }

      return;
    }

    // If there's empty input and the field is not required, set it to empty.
    if (!$element['#required']) {
      $form_state->setValueForElement($element, NULL);
      return;
    }

    // If there's empty input and the field is required, set an error. A
    // reminder of the required format in the message provides a good UX.
    $form_state->setError($element, t('The %field date is required. Please enter a date in the format %format.', ['%field' => $title, '%format' => Datetime::formatExample($format)]));
  }

  /**
   * Processes a date form element.
   *
   * @param array $element
   *   The form element to process. Properties used:
   *   - #attributes: An associative array containing:
   *     - type: The type of date field rendered.
   *   - #date_date_format: The date format used in PHP formats.
   *   - #date_first_day: The first day of the week shown in calendar.
//   *   - #date_year_range: The range of years shown in dropdown.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processDate(&$element, FormStateInterface $form_state, &$complete_form) {
    // Attach JS support for the date field, if we can determine which date
    // format should be used.
    if (!empty($element['#date_date_format'])) {
      $element['#attached']['library'][] = 'taarikh/taarikh';
      $element['#attributes']['data-taarikh-date-format'] = [$element['#date_date_format']];
//      $element['#attributes']['data-taarikh-year-range'] = [$element['#date_year_range']];
      $element['#attributes']['data-taarikh-first-day'] = [$element['#date_first_day']];
    }

    // Make sure we do the conversion only when the form is first build,
    // not subsequently when the form is shown again after validation
    // errors or similar scenarios.
    if (!$form_state->isProcessingInput()) {
      $taarikh_algorithm = !empty($element['#taarikh_algorithm']) ? $element['#taarikh_algorithm'] : 'fatimid_astronomical';
      /** @var \Drupal\taarikh\TaarikhAlgorithmPluginInterface $algorithm */
      $algorithm = \Drupal::service('plugin.manager.taarikh_algorithm')->createInstance($taarikh_algorithm);
      $format = DateFormat::load('html_date')->getPattern();

      try {
        $element['#value'] = $algorithm
          ->convertFromDateFormat($element['#value'])
          ->getFormatter()
          ->format($format);
      }
      catch (\Exception $ex) {
        $element['#value'] = NULL;
      }
    }

    return $element;
  }

  /**
   * Adds form-specific attributes to a 'date' #type element.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #options, #description, #required,
   *   #attributes, #id, #name, #type, #min, #max, #step, #value, #size. The
   *   #name property will be sanitized before output. This is currently done by
   *   initializing Drupal\Core\Template\Attribute with all the attributes.
   *
   * @return array
   *   The $element with prepared variables ready for #theme 'input__date'.
   */
  public static function preRenderDate($element) {
    Element::setAttributes($element, ['id', 'name', 'type', 'min', 'max', 'step', 'value', 'size']);
    static::setAttributes($element, ['form-' . $element['#attributes']['type']]);

    return $element;
  }

}
