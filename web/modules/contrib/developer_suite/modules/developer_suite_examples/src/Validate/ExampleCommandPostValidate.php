<?php

namespace Drupal\developer_suite_examples\Validate;

use Drupal\developer_suite\Collection\ViolationCollectionInterface;
use Drupal\developer_suite\Validator\Validator;

/**
 * Class ExampleCommandPostValidate.
 *
 * @package Drupal\developer_suite_examples\Validate
 */
class ExampleCommandPostValidate extends Validator {

  /**
   * Validates a value.
   *
   * When used as a post validator the command outcome (the return value from
   * your command handlers handle() method) gets passed as the $value
   * parameter.
   *
   * A post validator can be used to validate your command outcome. If the
   * post validation fails the postValidationMethod() in your command handler
   * gets invoked passing a
   * \Drupal\developer_suite\Collection\ViolationCollection object containing
   * the violations.
   *
   * @param mixed $value
   *   The value to be validated.
   * @param \Drupal\developer_suite\Collection\ViolationCollectionInterface $violationCollection
   *   The violations.
   */
  public function validate($value, ViolationCollectionInterface $violationCollection) {
    if ($value !== TRUE) {
      // If your validation fails add $this as a violation.
      $violationCollection->addViolation($this);
    }
  }

}
