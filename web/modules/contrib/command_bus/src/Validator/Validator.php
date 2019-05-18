<?php

namespace Drupal\command_bus\Validator;

/**
 * Class Validator.
 *
 * @package Drupal\command_bus\Validator
 */
abstract class Validator implements ValidatorInterface {

  /**
   * The violation message.
   *
   * @var string
   */
  protected $message;

  /**
   * Validates a value.
   *
   * @param mixed $value
   *   The value to be validated.
   * @param \Drupal\command_bus\Validator\Violations $violations
   *   The violations.
   */
  abstract public function validate($value, Violations $violations);

  /**
   * Returns the violation message.
   *
   * @return string
   *   The violation message.
   */
  public function getMessage() {
    return $this->message;
  }

}
