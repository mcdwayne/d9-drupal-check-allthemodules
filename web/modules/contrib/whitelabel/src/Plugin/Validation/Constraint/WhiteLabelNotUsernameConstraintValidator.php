<?php

namespace Drupal\whitelabel\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the WhiteLabelNotUsername constraint.
 */
class WhiteLabelNotUsernameConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (
      // Find white label as value of root context and extract username.
      (!empty($this->context->getRoot()->getValue()->getOwner())) &&
      $items->first()->value === $this->context->getRoot()->getValue()->getOwner()->getAccountName()
    ) {
      $this->context->addViolation($constraint->message);
    }
  }

}
