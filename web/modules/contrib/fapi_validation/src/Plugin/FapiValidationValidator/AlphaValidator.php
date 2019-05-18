<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationValidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\fapi_validation\FapiValidationValidatorsInterface;
use Drupal\fapi_validation\Validator;

/**
 * Fapi Validation Plugin for Aplha validation.
 *
 * @FapiValidationValidator(
 *   id = "alpha",
 *   error_message = "Use only alpha characters at %field."
 * )
 */
class AlphaValidator implements FapiValidationValidatorsInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(Validator $validator, array $element, FormStateInterface $form_state) {
    return (bool) preg_match('/^[\pL]++$/uD', (string) $validator->getValue());
  }

}
