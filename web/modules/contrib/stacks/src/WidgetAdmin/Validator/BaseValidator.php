<?php

namespace Drupal\stacks\WidgetAdmin\Validator;

/**
 * Class BaseValidator.
 * @package Drupal\stacks\WidgetAdmin\Validator
 */
abstract class BaseValidator implements ValidatorInterface {

  // Stores the $form_state object for the form.
  protected $formState = FALSE;

  // Stores the error message on validation failure.
  protected $errorMessage;
  
  /**
   * BaseValidator constructor.
   * 
   * @param string $error_message
   *   The error message to display when validation fails.
   * 
   */
  public function __construct($error_message) {
    $this->errorMessage = $error_message;
  }

  /**
   * Stores the form state from the form.
   *
   * @param object $form_state
   * 
   * @return void
   */
  public function setFormState($form_state) {
    $this->formState = $form_state;
  }

  /**
   * Gets the form state for the form.
   *
   * @param object $form_state
   * 
   * @return void
   */
  public function getFormState() {
    return $this->formState;
  }

  /**
   * Returns error message.
   * 
   * @return string
   *   Returns the string for the error message.
   */
  public function getErrorMessage() {
    return $this->errorMessage;
  }

}