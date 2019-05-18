<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationValidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\fapi_validation\FapiValidationValidatorsInterface;
use Drupal\fapi_validation\Validator;

/**
 * Fapi Validation Plugin for Match Field validation.
 *
 * @FapiValidationValidator(
 *   id = "match_field",
 *   error_message = "%field value does not match other field."
 * )
 */
class MatchFieldValidator implements FapiValidationValidatorsInterface {

  /**
   * {@inheritdoc}
   *
   * @todo Find a way to set match field for nested element Ex. $form['contact']['city']. Maybe something like 'match_field[contact/field]' ?
   */
  public function validate(Validator $validator, array $element, FormStateInterface $form_state) {
    $params = $validator->getParams();
    $value = $validator->getValue();

    return $form_state->hasValue($params[0]) && $value == $form_state->getValue($params[0]);
  }

}
