<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationValidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\fapi_validation\FapiValidationValidatorsInterface;
use Drupal\fapi_validation\Validator;

/**
 * Fapi Validation Plugin for Alpha Dash validation.
 *
 * @FapiValidationValidator(
 *   id = "alpha_dash",
 *   error_message = "Use only alpha numerics, hyphen and underscore at %field."
 * )
 */
class AlphaValidator implements FapiValidationValidatorsInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(Validator $validator, array $element, FormStateInterface $form_state) {
    return (bool) preg_match('/^[-\pL\pN_]+$/uD', (string) $validator->getValue());
  }

}
