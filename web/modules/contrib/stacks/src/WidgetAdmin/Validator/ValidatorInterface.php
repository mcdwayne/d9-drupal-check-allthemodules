<?php

namespace Drupal\stacks\WidgetAdmin\Validator;

/**
 * Interface ValidatorInterface.
 * @package Drupal\stacks\WidgetAdmin\Validator
 */
interface ValidatorInterface {

  /**
   * Validates a field.
   * 
   * This method is meant to make sure the data in this field is correct.
   * 
   * @param object $form_state
   *   The $form_state object for the form.
   * @param string $field_value
   *   The value of the field we are validating.
   * 
   * @return boolean
   *   Returns true/false if the validation passes or fails.
   */
  public function validates($field_value);

}