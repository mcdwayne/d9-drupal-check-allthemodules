<?php

namespace Drupal\developer_suite_examples\Validate;

use Drupal\developer_suite\Collection\ViolationCollectionInterface;
use Drupal\developer_suite\Validator\Validator;

/**
 * Class ExampleCommandPreValidate.
 *
 * @package Drupal\developer_suite_examples\Validate
 */
class ExampleCommandPreValidate extends Validator {

  /**
   * Validates a value.
   *
   * When used as a pre validator the command gets passed as the $value
   * parameter.
   *
   * A pre validator can be used to test your command before it is
   * executed by the command bus. If the pre validation fails the
   * preValidationMethod() in your command handler gets invoked passing a
   * \Drupal\developer_suite\Collection\ViolationCollection object containing
   * the violations.
   *
   * @param mixed $value
   *   The value to be validated.
   * @param \Drupal\developer_suite\Collection\ViolationCollectionInterface $violationCollection
   *   The violations.
   */
  public function validate($value, ViolationCollectionInterface $violationCollection) {
    if (!$value) {
      // If your validation fails add $this as a violation.
      $violationCollection->addViolation($this);
    }
  }

}
