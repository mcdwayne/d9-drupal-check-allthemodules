<?php

namespace Drupal\persian_fields\Plugin\Validation\Constraint;


use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique integer.
 *
 * @Constraint(
 *   id = "sheba",
 *   label = @Translation("Sheba", context = "Validation"),
 *   type = "string"
 * )
 */
class Sheba extends Constraint {

  public static $message = 'This value is not a valid sheba number.';
}