<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationValidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\fapi_validation\FapiValidationValidatorsInterface;
use Drupal\fapi_validation\Validator;

/**
 * Fapi Validation Plugin for Range validation.
 *
 * @FapiValidationValidator(
 *   id = "range",
 *   error_message = "%field value is out of range."
 * )
 */
class RangeValidator implements FapiValidationValidatorsInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(Validator $validator, array $element, FormStateInterface $form_state) {
    $params = $validator->getParams();
    $value = $validator->getValue();

    $min = $params[0];
    $max = $params[1];

    return ($min <= $value && $max >= $value);
  }

}
