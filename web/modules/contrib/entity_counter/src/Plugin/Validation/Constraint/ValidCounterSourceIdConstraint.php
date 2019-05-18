<?php

namespace Drupal\entity_counter\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if an entity field is a valid entity source ID value.
 *
 * @Constraint(
 *   id = "ValidCounterSourceId",
 *   label = @Translation("Valid entity counter source ID field constraint", context = "Validation"),
 * )
 */
class ValidCounterSourceIdConstraint extends Constraint {

  public $message = 'Nonexistent entity counter source ID: @plugin_id.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\entity_counter\Plugin\Validation\Constraint\CounterSourceIdFieldValueValidator';
  }

}
