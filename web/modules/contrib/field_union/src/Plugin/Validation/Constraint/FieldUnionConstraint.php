<?php

namespace Drupal\field_union\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Defines an access validation constraint for field unions.
 *
 * @Constraint(
 *   id = "FieldUnionConstraint",
 *   label = @Translation("Field union constraint.", context = "Validation"),
 * )
 */
class FieldUnionConstraint extends Constraint {

}
