<?php

namespace Drupal\persian_fields\Plugin\Validation\Constraint;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IranPhoneValidator extends ConstraintValidator {

  public function validate($value, Constraint $constraint) {
    if (!$this->isIranPhone($value)) {
      $this->context->addViolation(IranPhone::$message, []);
    }
  }

  /**
   * @param $value
   *
   * @return bool
   */
  private function isIranPhone($value) {
    return (bool) preg_match('/^0[2-9][0-9]{7,9}+$/', $value);
  }

}