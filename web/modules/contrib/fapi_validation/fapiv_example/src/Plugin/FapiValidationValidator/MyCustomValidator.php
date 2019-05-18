<?php

namespace Drupal\fapiv_example\Plugin\FapiValidationValidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\fapi_validation\FapiValidationValidatorsInterface;
use Drupal\fapi_validation\Validator;

/**
 * Provides a custom validation.
 *
 * Field must have JonhDoe as value.
 *
 * @FapiValidationValidator(
 *   id = "custom_validator",
 *   error_callback = "processError"
 * )
 */
class MyCustomValidator implements FapiValidationValidatorsInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(Validator $validator, array $element, FormStateInterface $form_state) {
    return $validator->getValue() == 'JonhDoe';
  }

  /**
   * Process custom error.
   *
   * @param Drupal\fapi_validation\Validator $validator
   *   Validator.
   * @param array $element
   *   Form element.
   *
   * @return string
   *   Error message.
   */
  public static function processError(Validator $validator, array $element) {
    $params = [
      '%value' => $validator->getValue(),
      '%field' => $element['#title'],
    ];
    return \t("You must enter 'JonhDoe' as value and not '%value' at field %field", $params);
  }

}
