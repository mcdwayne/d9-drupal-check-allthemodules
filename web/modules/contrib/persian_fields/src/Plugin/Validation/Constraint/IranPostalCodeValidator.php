<?php

namespace Drupal\persian_fields\Plugin\Validation\Constraint;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IranPostalCodeValidator extends ConstraintValidator {

  public function validate($value, Constraint $constraint) {
    if (!$this->isPostalCode($value)) {
      $this->context->addViolation(IranPostalCode::$message, []);
    }
  }

  /**
   * @param $value
   *
   * @return bool
   */
  private function isPostalCode($value) {
    return (bool) preg_match("/^(\d{10})$/", $value);
  }

}