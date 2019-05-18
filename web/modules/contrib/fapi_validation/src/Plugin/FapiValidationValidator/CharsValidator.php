<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationValidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\fapi_validation\FapiValidationValidatorsInterface;
use Drupal\fapi_validation\Validator;

/**
 * Fapi Validation Plugin for Character validation.
 *
 * @FapiValidationValidator(
 *   id = "chars",
 *   error_message = "Use only alpha characters at %field."
 * )
 */
class CharsValidator implements FapiValidationValidatorsInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(Validator $validator, array $element, FormStateInterface $form_state) {
    $params = $validator->getParams();
    $value = $validator->getValue();
    $size = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);

    for ($i = 0; $i < $size; $i++) {
      $current = substr($value, $i, 1);
      if (!in_array($current, $params)) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
