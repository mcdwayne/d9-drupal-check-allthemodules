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

    $query = \Drupal::database()->select('node_field_data', 'n')->condition('type', 'dataset')->condition('title', $item->value);
    $query->addExpression('COUNT(*)');
    $count = $query->execute()->fetchField();
    if ($count > 0) {
      $this->context->addViolation($constraint->duplicateTitle, ['%title' => $item->value]);
    }
  }
}
