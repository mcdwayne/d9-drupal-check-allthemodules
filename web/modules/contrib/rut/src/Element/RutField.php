<?php

/**
 * @file
 * Contains \Drupal\rut\Element\RutField.
 */

namespace Drupal\rut\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Textfield;
use Tifon\Rut\RutUtil;

/**
 * Provides a one-line text field form for rut element.
 *
 * @FormElement("rut_field")
 */
class RutField extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      // Set if load the javascript validator.
      '#validate_js' => FALSE,
      '#validate_submit' => TRUE,
      '#process' => array(
        array($class, 'processRutElementForm'),
        array($class, 'processAjaxForm'),
        array($class, 'processPattern'),
      ),
      '#element_validate' => array(
        array($class, 'validateRut'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderTextfield'),
        array($class, 'preRenderGroup'),
      ),
      '#theme' => 'input__rut_field',
      '#theme_wrappers' => array('form_element'),
    );
  }


  /**
   * #process callback for rut element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic input element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processRutElementForm(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#attributes']['class'][] = 'rut-field-input';
    $element['#attributes']['class'][] = 'form-text';

    if ($element['#validate_js']) {
      $element['#attached']['library'][] = 'rut/rut.rut';

      $element['#attributes']['class'][] = 'rut-validate-js';
      $message = trim($element['#message_js']) != '' ? $element['#message_js'] : t('Invalid Rut');
      $extra = '<div class="error-message-js invisible">' . $message . '</div>';
      $element['#children']['extra']['#markup'] = $extra;
    }

    $element['#size'] = $element['#maxlength'] = 13;

    return $element;
  }


  /**
   * Form element validation handler for #type 'rut'.
   */
  public static function validateRut(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = trim($element['#value']);
    if ($value === '') {
      return;
    }
    $label = isset($element['#title']) ? $element['#title'] : '';
    // Support for Rut Field
    if (isset($element['#field_name'])) {
      $instance = field_widget_instance($element, $form_state);
      $label = $instance['label'];
    }

    list($rut, $dv) = RutUtil::separateRut($value);
    // Validate the rut.
    if ($value && (!is_numeric($rut) || !RutUtil::validateRut($rut, $dv))) {
      $message = t('The Rut/Run @rut is invalid.', array('@rut' => $value));
      $form_state->setError($element, $message);
    }
  }

}
