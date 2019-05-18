<?php

namespace Drupal\language_combination\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ForumLeaf constraint.
 */
class LanguageCombinationConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if ($value->language_source == $value->language_target) {
      $this->context->addViolation($constraint->noDifferentMessage);
    }

    foreach ($value->getParent() as $combination) {
      if ($combination->language_source == $value->language_source && $combination->language_target == $value->language_target) {
        if ($value != $combination && $value->getName() > $combination->getName()) {
          $this->context->addViolation($constraint->uniqueMessage);
        }
      }
    }
  }

}
