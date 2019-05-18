<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationValidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\fapi_validation\FapiValidationValidatorsInterface;
use Drupal\fapi_validation\Validator;

/**
 * Fapi Validation Plugin for Numeric validation.
 *
 * @FapiValidationValidator(
 *   id = "numeric",
 *   error_message = "Use only numbers at %field."
 * )
 */
class NumericValidator implements FapiValidationValidatorsInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(Validator $validator, array $element, FormStateInterface $form_state) {
    return is_numeric($validator->getValue());
  }

}
