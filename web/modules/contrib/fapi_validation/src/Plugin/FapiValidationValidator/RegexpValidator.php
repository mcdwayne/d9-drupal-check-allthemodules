<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationValidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\fapi_validation\FapiValidationValidatorsInterface;
use Drupal\fapi_validation\Validator;

/**
 * Fapi Validation Plugin for Regex validation.
 *
 * @FapiValidationValidator(
 *   id = "regexp",
 *   error_message = "%field value does not match rule."
 * )
 */
class RegexpValidator implements FapiValidationValidatorsInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(Validator $validator, array $element, FormStateInterface $form_state) {
    $params = $validator->getParams();
    $value = $validator->getValue();

    // Some FAPI elements types, such as those provided by the Date API module,
    // will come in as an array (with date in one element and time in another).
    // To handle this use-case we simply implode them into a string.
    if (is_array($value)) {
      // Using array filter ensures that empty array elements do not cause an
      // extra space to be added to the value. We can't use trim to fix this
      // issue cause trim will remove all trailing whitespace in a string,
      // which may be meaningful.
      $value = implode(' ', array_filter($value));
    }

    return (bool) preg_match($params[0], (string) $value);
  }

}
