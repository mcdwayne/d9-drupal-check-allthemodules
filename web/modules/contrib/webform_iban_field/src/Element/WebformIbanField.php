<?php

namespace Drupal\webform_iban_field\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\Constraints\Iban;
use Symfony\Component\Validator\Validation;


/**
 * Provides a 'webform_iban_field'.
 *
 * @FormElement("webform_iban_field")
 */
class WebformIbanField extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#size' => 60,
      '#process' => [
        [$class, 'processWebformIbanField'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateWebformIbanField'],
      ],
      '#pre_render' => [
        [$class, 'preRenderWebformIbanField'],
      ],
      '#theme' => 'input__webform_iban_field',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Processes 'webform_iban_field' element.
   */
  public static function processWebformIbanField(&$element, FormStateInterface $form_state, &$complete_form) {
    return $element;
  }

  /**
   * Webform element validation handler for #type 'webform_iban_field'.
   */
  public static function validateWebformIbanField(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add custom validation logic.
    $name = $element['#name'];
    $value = $form_state->getValue($name);
    $valid = TRUE;

    if ($element['#webform_multiple'] && !$value) {
      $value = $element['#value'];
    }

    if ($value) {
      $validator = Validation::createValidator();
      $violations = $validator->validate($value, [
        new Iban(),
      ]);

      if (0 !== count($violations)) {
        $valid = FALSE;
      }
    }

    if (!$valid) {
      if (isset($element['#title'])) {
        $tArgs = array(
          '%name' => empty($element['#title']) ? $element['#parents'][0] : $element['#title'],
          '%value' => $value,
        );
        $form_state->setError(
          $element,
          t('The value %value for element %name is not a valid IBAN.', $tArgs)
        );
      } else {
        $form_state->setError($element);
      }
    }
  }

  /**
   * Prepares a #type 'webform_iban_field_multiple' render element for theme_element().
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for theme_element().
   */
  public static function preRenderWebformIbanField(array $element) {
    $element['#attributes']['type'] = 'text';
    Element::setAttributes($element, ['id', 'name', 'value', 'size', 'maxlength', 'placeholder']);
    static::setAttributes($element, ['form-text', 'webform-iban-field']);
    return $element;
  }

}
