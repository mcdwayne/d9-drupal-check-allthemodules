<?php

namespace Drupal\field_nif\Element;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Textfield;
use Drupal\field_nif\NifUtils;

/**
 * Provides a NIF/NIE/CIF render element.
 *
 * Properties:
 * - #supported_types: (optional) An array with the supported document types,
 *     available:
 *       - nif: Spanish with a national identity document assigned by the
 *              Ministry of the Interior.
 *       - nie: Foreigners resident in Spain and identified by the Police with
 *              an identification number.
 *       - cif: For legal persons or entities in general, tax identification
 *              code.
 * Usage example:
 * @code
 * $form['nif'] = array(
 *   '#type' => 'nif',
 *   '#default_value' => $this->getNif(),
 *   '#supported_types' => [
 *     'nif',
 *     'nie',
 *     'cif',
 *   ],
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Textfield
 *
 * @FormElement("nif")
 */
class Nif extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#default_value' => NULL,
      '#size' => 12,
      '#maxlength' => 10,
      '#supported_types' => [
        'nif',
        'nie',
        'cif',
      ],
      '#process' => [
        [$class, 'processAutocomplete'],
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#element_validate' => [
        [$class, 'validateNif'],
      ],
      '#pre_render' => [
        [$class, 'preRenderTextfield'],
      ],
      '#theme' => 'input__textfield',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Form element validation handler for NIF/NIE/CIF elements.
   *
   * @param array $element
   *   The form element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function validateNif(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = trim($element['#value']);
    $supported_types = array_filter($element['#supported_types']);
    $form_state->setValueForElement($element, $value);

    if ($value !== '' && !NifUtils::validateNifCifNie($value, $element['#supported_types'])) {
      $form_state->setError($element, t('@value is not a valid @document_type document number.', [
        '@value' => $value,
        '@document_type' => Unicode::strtoupper(implode('/', $supported_types)),
      ]));
    }
  }

  /**
   * Prepares a #type 'nif' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderNif($element) {
    $element['#attributes']['type'] = 'nif';
    Element::setAttributes($element, [
      'id',
      'name',
      'value',
      'size',
      'maxlength',
      'placeholder',
      'supported_types',
    ]);
    static::setAttributes($element, ['form-nif']);

    return $element;
  }

}
