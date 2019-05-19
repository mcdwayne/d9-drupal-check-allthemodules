<?php

namespace Drupal\x_reference\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice as SymfonyChoice;


/**
 * "XReferencedEntity" constraint wrapper.
 *
 * @Constraint(
 *   id = "XReferencedEntityConstraint",
 *   label = @Translation("X-referenced entity", context = "Validation")
 * )
 */
class XReferencedEntityConstraint extends Constraint {

  public $message = 'Incorrect entity passed for x-reference: %entity_source:%entity_type can not be used as %mode for %x_reference_type reference';

}
