<?php

namespace Drupal\telephone_type\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Telephone constraint.
 *
 * @Constraint(
 *   id = "TelephoneTypeValidation",
 *   label = @Translation("Telephone", context = "Validation")
 * )
 */
class TelephoneTypeValidationContraint extends Constraint {

  public $message = "@number is not a valid US phone number.";

}
