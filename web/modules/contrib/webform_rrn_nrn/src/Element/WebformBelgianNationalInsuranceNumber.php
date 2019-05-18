<?php

namespace Drupal\webform_rrn_nrn\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'webform_belgian_national_insurance_number'.
 *
 * @FormElement("webform_belgian_national_insurance_number")
 */
class WebformBelgianNationalInsuranceNumber extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    $element = [
      '#input' => TRUE,
      '#type' => 'textfield',
      '#size' => 15,
      '#process' => [
        [$class, 'processBelgianNationalInsuranceNumber'],
        // [$class, 'processAjaxForm'],.
      ],
      '#element_validate' => [
          [$class, 'validateBelgianNationalInsuranceNumber'],
      ],
      '#pre_render' => [
          [$class, 'preRenderBelgianNationalInsuranceNumber'],
      ],
      '#theme' => 'input__webform_example_element',
      '#theme_wrappers' => ['form_element'],
    ];
    return $element;
  }

  /**
   * Processes a 'BelgianNationalInsuranceNumberm' element.
   */
  public static function processBelgianNationalInsuranceNumber(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add and manipulate your element's properties and callbacks.
    return $element;
  }

  /**
   * Webform element validation handler for #type 'webform_belgian_national_insurance_number'.
   */
  public static function validateBelgianNationalInsuranceNumber(&$element, FormStateInterface $form_state, &$complete_form) {
    $rrn_nrn = $form_state->getValue($element['#webform_key']);
    $current_century_year = (int) ("20" . substr($rrn_nrn, 0, 2));
    $year = (int) date("Y");
    $rrn_calc_value = (int) (substr($rrn_nrn, 0, 6) . substr($rrn_nrn, 7, 3));
    $check_digit = (int) substr($rrn_nrn, 11, 2);
    $remainder = $rrn_calc_value % 97;
    $remainder_2000 = ($rrn_calc_value + 2000000000) % 97;

    if (!(((97 - $remainder) == $check_digit) || (((97 - $remainder_2000) == $check_digit) && $current_century_year <= $year))) {
      $form_state->setError($element, $element['#error_message']);
    }
  }

  /**
   * Prepares a #type 'webform_belgian_national_insurance_number' render element for theme_element().
   */
  public static function preRenderBelgianNationalInsuranceNumber(array $element) {
    $element['#attributes']['type'] = 'text';
    $element['#attributes']['data-inputmask-mask'] = '999999-999-99';
    $element['#attached']['library'][] = 'webform/webform.element.inputmask';
    Element::setAttributes($element, [
      'id',
      'name',
      'value',
      'size',
      'maxlength',
      'placeholder',
    ]);
    static::setAttributes($element, [
      'form-text',
      'rrn-nrn',
      'js-webform-input-mask',
    ]);
    return $element;
  }

}
