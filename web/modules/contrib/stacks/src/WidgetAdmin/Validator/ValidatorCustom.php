<?php

namespace Drupal\stacks\WidgetAdmin\Validator;

/**
 * Class ValidatorCustom.
 * @package Drupal\stacks\WidgetAdmin\Validator
 */
class ValidatorCustom extends BaseValidator {

  // This is the custom function we are calling to validate this field.
  protected $validatorFunction = FALSE;
  
  /**
   * ValidatorCustom constructor.
   * 
   * @param string $error_message
   * @param function $validator_function
   *   The function to run the validation on. This function needs to take a 
   *   $field_value and a $form_state (object) argument. 
   */
  public function __construct($error_message, $validator_function) {
    parent::__construct($error_message);

    if (is_callable($validator_function)) {
      $this->validatorFunction = $validator_function;
    }
    else {
      \Drupal::logger('stacks')->error("No 'validator_function' specified for validating the field with the error message '%error'.", array('%error' => $this->getErrorMessage()));
    }
  }

  /**
   * @inheritDoc
   */
  public function validates($field_value) {
    if (!$this->validatorFunction) {
      return FALSE;
    }

    if (!$this->getFormState()) {
      \Drupal::logger('stacks')->error("No 'formState' specified for validating the field with the error message '%error'.", array('%error' => $this->getErrorMessage()));
      return FALSE;
    }

    // Validates the value of this field in the custom defined function.
    $validator_function = $this->validatorFunction;
    return $validator_function($this->getFormState(), $field_value);
  }

}
