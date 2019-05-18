<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationValidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\fapi_validation\FapiValidationValidatorsInterface;
use Drupal\fapi_validation\Validator;

/**
 * Fapi Validation Plugin for Digit validation.
 *
 * @FapiValidationValidator(
 *   id = "digit",
 *   error_message = "Use only digits on %field."
 * )
 */
class DigitValidator implements FapiValidationValidatorsInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(Validator $validator, array $element, FormStateInterface $form_state) {
    return (bool) preg_match('/^\d+$/', (string) $validator->getValue());
  }

}
