<?php

namespace Drupal\reference_map_adva\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ReferenceMapMap constraint.
 */
class ReferenceMapAdvaLastStepIsUserValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($reference_map_config, Constraint $constraint) {
    if ($reference_map_config->type == 'advanced_access') {
      $map = $reference_map_config->map;
      $step = end($map);

      // Ensure the last step's entity_type key is user.
      if ($step['entity_type'] !== 'user') {
        $this->context->buildViolation($constraint->message)
          ->atPath('map')
          ->addViolation();
      }
    }
  }

}
