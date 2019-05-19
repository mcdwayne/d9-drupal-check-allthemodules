<?php

namespace Drupal\rut_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for rut.
 *
 * @Constraint(
 *   id = "RutFieldType",
 *   label = @Translation("Rut data valid for rut type.", context = "Validation"),
 * )
 */
class RutFieldTypeConstraint extends Constraint {

  public $message = 'The Rut %rut is invalid.';

}
