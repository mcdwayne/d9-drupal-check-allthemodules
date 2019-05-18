<?php

namespace Drupal\quadstat_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that there are no duplicate titles of datasets before saving
 *
 * @Constraint(
 *   id = "DuplicateTitle",
 *   label = @Translation("Duplicate Title", context = "Validation"),
 * )
 */
class DuplicateTitle extends Constraint {
  // The message that will be shown if there is a duplicate title
  public $duplicateTitle = 'Duplicate titles are not permitted. Please choose a unique title for your dataset.';
}
