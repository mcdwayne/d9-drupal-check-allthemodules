<?php

namespace Drupal\entity_reference_validators\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Entity Reference circular reference constraint.
 *
 * Verifies that referenced entities do not lead to a circular reference.
 *
 * @Constraint(
 *   id = "CircularReference",
 *   label = @Translation("Entity Reference circular reference", context = "Validation")
 * )
 */
class CircularReferenceConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'This entity (%type: %id) cannot be referenced.';

}
