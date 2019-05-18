<?php

namespace Drupal\quadstat_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates Dataset titles (no duplicates)
 */
class DuplicateTitleConstraintValidator extends ConstraintValidator {
  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    // This is a single-item field so we only need to
    // validate the first item
    $item = $items->first();

    // If there is no value we don't need to validate anything
    if(!isset($item)) {
      return NULL;
    }

    // Check that the value is in the format HH:MM:SS
    if(!count(db_select('node', 'n')->fields('n')->condition('title', $item->value)->execute()->fetchAssoc())) {
      // The value is an incorrect format, so we set a 'violation'
      // aka error. The key we use for the constraint is the key
      // we set in the constraint, in this case $incorrectDurationFormat.
      $this->context->addViolation($constraint->duplicateTitle, ['%title' => $item->value]);
    }
  }
}
