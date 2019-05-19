<?php

namespace Drupal\whitelabel\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a value is a valid entity type.
 *
 * @Constraint(
 *   id = "WhiteLabelNotUsername",
 *   label = @Translation("Not equal to username", context = "Validation"),
 * )
 */
class WhiteLabelNotUsernameConstraint extends Constraint {

  public $message = 'Due to security concerns this value cannot be the same as your user name.';

}
