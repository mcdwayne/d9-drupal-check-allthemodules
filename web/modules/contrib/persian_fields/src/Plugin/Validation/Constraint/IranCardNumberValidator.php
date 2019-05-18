<?php

namespace Drupal\persian_fields\Plugin\Validation\Constraint;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IranCardNumberValidator extends ConstraintValidator {

  public function validate($value, Constraint $constraint) {
    if (!$this->isIranCardNumber($value)) {
      $this->context->addViolation(IranCardNumber::$message, []);
    }
  }

  /**
   * @param $value
   *
   * @return bool
   */
  private function isIranCardNumber($value) {
    if (!preg_match('/^\d{16}$/', $value)) {
      return FALSE;
    }

    $sum = 0;

    for ($position = 1; $position <= 16; $position++) {
      $temp = $value[$position - 1];
      $temp = $position % 2 === 0 ? $temp : $temp * 2;
      $temp = $temp > 9 ? $temp - 9 : $temp;

      $sum += $temp;
    }

    return (bool) ($sum % 10 === 0);
  }

}