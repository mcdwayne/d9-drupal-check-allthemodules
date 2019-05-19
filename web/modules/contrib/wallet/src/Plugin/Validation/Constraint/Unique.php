<?php

namespace Drupal\wallet\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldConstraint;

/**
 * Checks if a field is unique.
 *
 * @Constraint(
 *   id = "Unique",
 *   label = @Translation("Field unique", context = "Validation"),
 * )
 */

class Unique extends UniqueFieldConstraint {
  public $message = 'The currency %value is already taken.';
}
