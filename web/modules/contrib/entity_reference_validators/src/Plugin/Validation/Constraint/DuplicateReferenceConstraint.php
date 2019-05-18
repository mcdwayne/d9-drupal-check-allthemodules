<?php

namespace Drupal\entity_reference_validators\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Entity Reference duplicate reference constraint.
 *
 * Verifies that referenced entities do not duplicate a previous reference.
 *
 * @Constraint(
 *   id = "DuplicateReference",
 *   label = @Translation("Entity Reference duplicate reference", context = "Validation")
 * )
 */
class DuplicateReferenceConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The value %label has been entered multiple times.';

}
