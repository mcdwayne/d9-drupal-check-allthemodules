<?php

namespace Drupal\persian_fields\Plugin\Validation\Constraint;


use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique integer.
 *
 * @Constraint(
 *   id = "iran_mobile",
 *   label = @Translation("IranMobile", context = "Validation"),
 *   type = "string"
 * )
 */
class IranMobile extends Constraint {

  public static $message = 'This value is not a valid mobile number.';
}