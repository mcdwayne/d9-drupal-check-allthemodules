<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationValidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\fapi_validation\FapiValidationValidatorsInterface;
use Drupal\fapi_validation\Validator;

/**
 * Fapi Validation Plugin for Decimal validation.
 *
 * @FapiValidationValidator(
 *   id = "decimal",
 *   error_message = "Use only decimal on %field."
 * )
 */
class DecimalValidator implements FapiValidationValidatorsInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(Validator $validator, array $element, FormStateInterface $form_state) {
    $params = $validator->getParams();
    $value = $validator->getValue();

    if (count($params) == 2) {
      $pattern = '/^[0-9]{' . $params[0] . '}\.[0-9]{' . $params[1] . '}$/';
      return (bool) preg_match($pattern, (string) $value);
    }

    return (bool) filter_var($value, FILTER_VALIDATE_FLOAT);
  }

}
