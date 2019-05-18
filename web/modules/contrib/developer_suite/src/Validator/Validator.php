<?php

namespace Drupal\developer_suite\Validator;

use Drupal\developer_suite\Collection\ViolationCollectionInterface;

/**
 * Class Validator.
 *
 * @package Drupal\developer_suite\Validator
 */
abstract class Validator extends BaseValidator {

  /**
   * Validates a value.
   *
   * @param mixed $value
   *   The value to be validated.
   * @param \Drupal\developer_suite\Collection\ViolationCollectionInterface $violationCollection
   *   The violations.
   */
  abstract public function validate($value, ViolationCollectionInterface $violationCollection);

}
