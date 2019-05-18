<?php

namespace Drupal\command_bus\Validator;

use Drupal\command_bus\Command\CommandInterface;

/**
 * Class ValidCommandValidator.
 *
 * @package Drupal\command_bus\Validator
 */
class ValidCommandValidator extends Validator {

  public $message = 'The provided command is invalid.';

  /**
   * Validates a value.
   *
   * @param mixed $value
   *   The value to be validated.
   * @param \Drupal\command_bus\Validator\Violations $violations
   *   The violations.
   */
  public function validate($value, Violations $violations) {
    if (!$value instanceof CommandInterface) {
      $violations->addViolation($this);
    }
  }

}
