<?php

namespace Drupal\fapi_error_messages\Validate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Number;

/**
 * Provides a form element validation for numeric input.
 */
class ValidateNumberConstraint extends ValidateBase {

  /**
   * Form element validation handler for #type 'number'.
   *
   * Note that #required is validated by _form_validate() already.
   */
  public static function validate(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value'];
    if ($value === '') {
      return;
    }
    $name = empty($element['#title']) ? $element['#parents'][0] : $element['#title'];

    // Ensure the input is numeric.
    if (!is_numeric($value)) {
      $error_message = t('%name must be a number.', [
        '%name' => $name,
      ]);
      self::processErrorMessage($element, 'number', $error_message, $form_state);

      return;
    }

    // Ensure that the input is greater than the #min property, if set.
    if (isset($element['#min']) && $value < $element['#min']) {
      $error_message = t('%name must be higher than or equal to %min.', [
        '%name' => $name,
        '%min' => $element['#min'],
      ]);
      self::processErrorMessage($element, 'min', $error_message, $form_state);
    }

    // Ensure that the input is less than the #max property, if set.
    if (isset($element['#max']) && $value > $element['#max']) {
      $error_message = t('%name must be lower than or equal to %max.', [
        '%name' => $name,
        '%max' => $element['#max'],
      ]);
      self::processErrorMessage($element, 'max', $error_message, $form_state);
    }

    // Check that the input is an allowed multiple of #step (offset by #min if
    // #min is set).
    if (isset($element['#step']) && strtolower($element['#step']) != 'any') {
      $offset = isset($element['#min']) ? $element['#min'] : 0.0;
      if (!Number::validStep($value, $element['#step'], $offset)) {
        $error_message = t('%name is not a valid number.', [
          '%name' => $name,
        ]);
        self::processErrorMessage($element, 'step', $error_message, $form_state);
      }
    }
  }

}
