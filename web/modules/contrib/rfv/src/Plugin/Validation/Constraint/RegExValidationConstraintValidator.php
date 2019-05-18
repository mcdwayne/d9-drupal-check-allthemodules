<?php

namespace Drupal\regex_field_validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Implementing Custom RegEx Validator Class.
 */
class RegExValidationConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $field = $this->context->getValue();
    $value = $field->getValue();
    if (!empty($value)) {
      if (!preg_match_all($constraint->regex, $value[0]['value'])) {
        $this->context->addViolation(t($constraint->errorMessage));
      };
    }
  }

}
