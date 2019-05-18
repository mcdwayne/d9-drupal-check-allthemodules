<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationValidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\fapi_validation\FapiValidationValidatorsInterface;
use Drupal\fapi_validation\Validator;

/**
 * Fapi Validation Plugin for Lenght validation.
 *
 * @FapiValidationValidator(
 *   id = "length",
 *   error_message = "Invalid size of %field value."
 * )
 */
class LengthValidator implements FapiValidationValidatorsInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(Validator $validator, array $element, FormStateInterface $form_state) {
    $params = $validator->getParams();
    $value = $validator->getValue();
    $size = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    if (count($params) == 1) {
      return $size == (int) $params[0];
    }
    elseif (count($params) == 2) {
      if ($params[1] == '*') {
        return ($size >= (int) $params[0]);
      }
      return ($size >= (int) $params[0] && $size <= (int) $params[1]);
    }
  }

}
