<?php

namespace Drupal\fapi_validation;

use Drupal\Core\Form\FormStateInterface;

/**
 * Fapi Validation Validator Plugin Interface.
 */
interface FapiValidationValidatorsInterface {

  /**
   * Execute validation.
   *
   * @param \Drupal\fapi_validation\Validator $validator
   *   Validator.
   * @param array $element
   *   Form Element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   *
   * @return bool
   *   Check.
   */
  public function validate(Validator $validator, array $element, FormStateInterface $form_state);

}
