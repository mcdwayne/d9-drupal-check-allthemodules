<?php

namespace Drupal\cpf\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form input element for entering an CPF number.
 *
 * Example usage:
 * @code
 * $form['cpf'] = [
 *   '#type' => 'cpf',
 *   '#title' => $this->t('CPF number'),
 * ];
 * @end
 *
 * @see \Drupal\Core\Render\Element\Render\Textfield
 *
 * @FormElement("cpf")
 */
class Cpf extends FormElement {

  /**
   * Defines canonical mask format.
   */
  const CANONICAL_MASK = '000.000.000-00';

  /**
   * Defines a mask for numbers.
   */
  const DIGITS_MASK = '00000000000';

  /**
   * Defines the max length for a CPF number using a canonical mask.
   */
  const MAX_LENGTH_CANONICAL_MASK = 14;

  /**
   * Defines the max length for a CPF number.
   */
  const MAX_LENGTH_DIGITS_MASK = 9;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#size' => 60,
      '#maxlength' => self::MAX_LENGTH_CANONICAL_MASK,
      '#autocomplete_route_name' => FALSE,
      '#description' => '',
      '#mask' => TRUE,
      '#process' => [
        [$class, 'processAutocomplete'],
        [$class, 'processAjaxForm'],
        [$class, 'processPattern'],
      ],
      '#element_validate' => [
        [$class, 'validateCpf'],
      ],
      '#pre_render' => [
        [$class, 'preRenderCpf'],
      ],
      '#theme' => 'input__cpf',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Form element validation handler for #type 'cpf'.
   *
   * Note that #maxlength and #required is validated by _form_validate()
   * already.
   */
  public static function validateCpf(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = trim($element['#value']);
    $form_state->setValueForElement($element, $value);

    if ($value !== '' && !\Drupal::service('cpf')->isValid($value)) {
      $form_state->setError($element, t('The CPF number %cpf is not valid.', ['%cpf' => $value]));
    }
  }

  /**
   * Prepares a #type 'cpf' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderCpf(array $element) {
    $element['#attributes']['type'] = 'cpf';

    $element['#maxlength'] = $element['#mask'] ? self::MAX_LENGTH_CANONICAL_MASK : self::MAX_LENGTH_DIGITS_MASK;

    $data['cpf']['mask_plugin']['elements'][$element['#id']] = [
      'id' => $element['#id'],
      'mask' => $element['#mask'] ? self::CANONICAL_MASK : self::DIGITS_MASK,
    ];

    $element['#attached'] = [
      'library' => [
        'cpf/cpf',
      ],
      'drupalSettings' => $data,
    ];

    $attributes = ['id', 'name', 'value', 'size', 'maxlength', 'placeholder'];
    Element::setAttributes($element, $attributes);
    static::setAttributes($element, ['form-cpf']);

    return $element;
  }

}
