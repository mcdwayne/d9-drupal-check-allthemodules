<?php

namespace Drupal\persian_fields\Plugin\Validation\Constraint;


use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique integer.
 *
 * @Constraint(
 *   id = "iran_postal_code",
 *   label = @Translation("IranPostalCode", context = "Validation"),
 *   type = "string"
 * )
 */
class IranPostalCode extends Constraint {

  public static $message = 'This value is not a valid postal code.';
}