<?php

namespace Drupal\bookkeeping\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a Bookkeeping Entries constraint.
 *
 * @Constraint(
 *   id = "BookkeepingEntries",
 *   label = @Translation("Bookkeeping Entries", context = "Validation"),
 * )
 */
class BookkeepingEntriesConstraint extends Constraint {

  public $errorMessageCount = 'Transactions must have at least two entries.';

  public $errorMessageNotZero = 'Transactions must net to zero.';

}
