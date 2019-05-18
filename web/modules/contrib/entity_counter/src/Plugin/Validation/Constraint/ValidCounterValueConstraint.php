<?php

namespace Drupal\entity_counter\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if an entity transaction value has a valid value.
 *
 * @Constraint(
 *   id = "ValidCounterValueConstraint",
 *   label = @Translation("Valid entity transaction value constraint", context = "Validation"),
 *   type = { "entity" }
 * )
 */
class ValidCounterValueConstraint extends Constraint {

  public $message = '@field_name is not a valid number.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\entity_counter\Plugin\Validation\Constraint\CounterValueValidator';
  }

}
