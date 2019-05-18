<?php

namespace Drupal\bookkeeping\Plugin\Validation\Constraint;

use Drupal\bookkeeping\Plugin\Field\FieldType\BookkeepingEntryItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Bookkeeping Entries constraint.
 */
class BookkeepingEntriesConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    /** @var \Drupal\bookkeeping\Plugin\Validation\Constraint\BookkeepingEntriesConstraint $constraint */
    if (!isset($items) || count($items) < 2) {
      $this->context->buildViolation($constraint->errorMessageCount)
        ->addViolation();
      return;
    }

    $net = 0;

    foreach ($items as $delta => $item) {
      $multipler = $item->type == BookkeepingEntryItem::TYPE_DEBIT ? 1 : -1;
      $net += $multipler * $item->amount;
    }

    if ($net != 0) {
      $this->context->buildViolation($constraint->errorMessageNotZero)
        ->addViolation();
    }
  }

}
