<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationValidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\fapi_validation\FapiValidationValidatorsInterface;
use Drupal\fapi_validation\Validator;

/**
 * Fapi Validation Plugin for Alpha Numeric validation.
 *
 * @FapiValidationValidator(
 *   id = "alpha_numeric",
 *   error_message = "Use only alpha numerics characters at %field."
 * )
 */
class AlphaNumericValidator implements FapiValidationValidatorsInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(Validator $validator, array $element, FormStateInterface $form_state) {
    return (bool) preg_match('/^[\pL]++$/uD', (string) $validator->getValue());
  }

}
