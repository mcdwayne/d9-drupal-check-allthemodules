<?php

namespace Drupal\quadstat_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Duplicate titles are not allowed
 *
 * @Constraint(
 *   id = "DuplicateTitleConstraint",
 *   label = @Translation("Duplicate Dataset Title", context = "Validation"),
 * )
 */
class DuplicateTitleConstraint extends Constraint {
  // The message that will be shown if the format is incorrect.
  public $duplicateTitle = 'A dataset with that title already exists. Please choose a unique title.';
}
