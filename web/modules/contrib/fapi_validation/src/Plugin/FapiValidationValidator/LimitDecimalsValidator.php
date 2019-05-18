<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationValidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\fapi_validation\FapiValidationValidatorsInterface;
use Drupal\fapi_validation\Validator;

/**
 * Fapi Validation Plugin for Limit Decimals.
 *
 * @FapiValidationValidator(
 *   id = "limit_decimals",
 *   error_message = "Invalid value for %field or too many decimal digits."
 * )
 */
class LimitDecimalsValidator implements FapiValidationValidatorsInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(Validator $validator, array $element, FormStateInterface $form_state) {
    $params = $validator->getParams();
    $value = $validator->getValue();

    if (!is_numeric($value)) {
      return FALSE;
    }
    if (count($params) > 0) {
      $value = (float) $value;
      $pattern = '/^[^\.]*\.?[0-9]{0,' . $params[0] . '}$/';
      return (bool) preg_match($pattern, (string) $value);
    }
    return TRUE;
  }

}
