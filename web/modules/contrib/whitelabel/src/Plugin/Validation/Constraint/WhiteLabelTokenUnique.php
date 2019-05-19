<?php

namespace Drupal\whitelabel\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldConstraint;

/**
 * Checks if a white label token is unique on the site.
 *
 * @Constraint(
 *   id = "WhiteLabelTokenUnique",
 *   label = @Translation("Unique white label token", context = "Validation"),
 * )
 */
class WhiteLabelTokenUnique extends UniqueFieldConstraint {

  public $message = 'The token %value is already in use.';

}
