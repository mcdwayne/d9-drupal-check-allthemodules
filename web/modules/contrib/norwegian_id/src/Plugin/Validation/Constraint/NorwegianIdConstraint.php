<?php

namespace Drupal\norwegian_id\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Country constraint.
 *
 * @Constraint(
 *   id = "NorwegianId",
 *   label = @Translation("Norwegian National ID", context = "Validation"),
 *   type = { "norwegian_id" }
 * )
 */
class NorwegianIdConstraint extends Constraint {

  public $invalidFormatMessage = "The Personal ID is in the wrong format.";
  public $invalidBirthMessage = "The Individual number doesn't match with your year of birth.";
  public $invalidControlDigitsMessage = "Control digits don't match, please check your number.";

}
