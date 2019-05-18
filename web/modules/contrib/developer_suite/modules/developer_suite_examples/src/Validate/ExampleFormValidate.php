<?php

namespace Drupal\developer_suite_examples\Validate;

use Drupal\developer_suite\Collection\ViolationCollectionInterface;
use Drupal\developer_suite\Validator\FormValidator;

/**
 * Class ExampleFormValidate.
 *
 * @package Drupal\developer_suite_examples\Validate
 */
class ExampleFormValidate extends FormValidator {

  /**
   * Validates a value.
   *
   * @param mixed $value
   *   The value.
   * @param \Drupal\developer_suite\Collection\ViolationCollectionInterface $violationCollection
   *   The violation collection.
   */
  public function validate($value, ViolationCollectionInterface $violationCollection) {
    // Access the form state via the getFormState() method.
    $formState = $this->getFormState();
    // For example: get the form object.
    $formState->getFormObject();

    // Perform some validation.
    if ($value !== 'valid user input') {
      // If $value is not valid add a validation.
      $violationCollection->addViolation($this);
    }
  }

}
