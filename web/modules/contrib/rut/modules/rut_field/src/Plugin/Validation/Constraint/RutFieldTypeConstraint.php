<?php

/**
 * @file
 * Contains \Drupal\rut_field\Plugin\Validation\Constraint\RutFieldTypeConstraint.
 */

namespace Drupal\rut_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ExecutionContextInterface;
use Tifon\Rut\RutUtil;

/**
 * Validation constraint for rut.
 *
 * @Constraint(
 *   id = "RutFieldType",
 *   label = @Translation("Rut data valid for rut type.", context = "Validation"),
 * )
 */
class RutFieldTypeConstraint extends Constraint implements ConstraintValidatorInterface {

 public $message = 'The Rut %rut is invalid.';

 /**
  * @var \Symfony\Component\Validator\ExecutionContextInterface
  */
 protected $context;

 /**
  * {@inheritDoc}
  */
 public function initialize(ExecutionContextInterface $context) {
   $this->context = $context;
 }

 /**
  * {@inheritdoc}
  */
 public function validatedBy() {
   return get_class($this);
 }

 /**
  * {@inheritdoc}
  */
 public function validate($value, Constraint $constraint) {
   if (isset($value)) {
     $values = $value->getValue();
     if (!RutUtil::validateRut($values['value'])) {
       $this->context->addViolation($this->message, array('%rut' => $values['value']));
     }
   }
 }
}