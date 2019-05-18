<?php

namespace Drupal\chatbot\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if an entity field has a valid path value.
 *
 * @Constraint(
 *   id = "ValidPath",
 *   label = @Translation("Valid path constraint", context = "Validation"),
 * )
 */
class ValidPathConstraint extends Constraint {

  public $message = '%value value of @field_name field is not a valid url.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\chatbot\Plugin\Validation\Constraint\ValidPathValueValidator';
  }

}
