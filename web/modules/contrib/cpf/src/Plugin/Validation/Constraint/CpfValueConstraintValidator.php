<?php

namespace Drupal\cpf\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field is a CPF number.
 */
class CpfValueConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    // Verifica se o CPF é válido.
    if (!\Drupal::service('cpf')->isValid($value)) {
      $this->context->addViolation($constraint->message, [
        '%value' => $value,
      ]);
    }
  }

}
