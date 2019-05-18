<?php

namespace Drupal\developer_suite\Validator;

use Drupal\developer_suite\Collection\ViolationCollectionInterface;

/**
 * Interface BaseValidatorInterface.
 *
 * @package Drupal\developer_suite\Validator
 */
interface BaseValidatorInterface {

  /**
   * Returns the violation message.
   *
   * @return string
   *   The violation message.
   */
  public function getMessage();

  /**
   * Validates a value.
   *
   * @param mixed $value
   *   The value to be validated.
   * @param \Drupal\developer_suite\Collection\ViolationCollectionInterface $violationCollection
   *   The violations.
   */
  public function validate($value, ViolationCollectionInterface $violationCollection);

}
