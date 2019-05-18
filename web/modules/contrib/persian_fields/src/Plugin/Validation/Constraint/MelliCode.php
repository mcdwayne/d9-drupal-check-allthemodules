<?php

namespace Drupal\persian_fields\Plugin\Validation\Constraint;


use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique integer.
 *
 * @Constraint(
 *   id = "melli_code",
 *   label = @Translation("Melli Code", context = "Validation"),
 *   type = "string"
 * )
 */
class MelliCode extends Constraint {

  public static $message = 'This value is not a valid melli code.';
}