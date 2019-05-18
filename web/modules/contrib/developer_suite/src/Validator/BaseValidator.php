<?php

namespace Drupal\developer_suite\Validator;

/**
 * Class BaseValidator.
 *
 * @package Drupal\developer_suite\Validator
 */
abstract class BaseValidator implements BaseValidatorInterface {

  /**
   * The violation message.
   *
   * @var string
   */
  private $message;

  /**
   * ValidatorBase constructor.
   *
   * @param string $message
   *   The violation message.
   */
  public function __construct($message) {
    $this->message = $message;
  }

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
