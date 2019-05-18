<?php

namespace Drupal\command_bus\Validator;

/**
 * Interface ValidatorInterface.
 *
 * @package Drupal\command_bus\Validator
 */
interface ValidatorInterface {

  /**
   * Validates a value.
   *
   * @param mixed $value
   *   The value to be validated.
   * @param \Drupal\command_bus\Validator\Violations $violations
   *   The violations.
   */
  public function validate($value, Violations $violations);

}
