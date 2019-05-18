<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationValidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\fapi_validation\FapiValidationValidatorsInterface;
use Drupal\fapi_validation\Validator;
use Drupal\Component\Utility\UrlHelper;

/**
 * Fapi Validation Plugin for URL validation.
 *
 * @FapiValidationValidator(
 *   id = "url",
 *   error_message = "Invalid format of %field."
 * )
 */
class UrlValidator implements FapiValidationValidatorsInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(Validator $validator, array $element, FormStateInterface $form_state) {
    $params = $validator->getParams();
    return UrlHelper::isValid($validator->getValue(), !empty($params) && $params[0] == 'absolute');
  }

}
