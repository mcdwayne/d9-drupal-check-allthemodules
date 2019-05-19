<?php

namespace Drupal\rut_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\rut\Rut;

/**
 * Constraint validator to validate the rut.
 */
class RutFieldTypeConstraintValidator extends ConstraintValidator {

	/**
  * {@inheritdoc}
  */
 public function validate($value, Constraint $constraint) {
   if (isset($value)) {
     $values = $value->getValue();
     if (!Rut::validateRut($values['value'])) {
       $this->context->addViolation($constraint->message, ['%rut' => $values['value']]);
     }
   }
 }
}
