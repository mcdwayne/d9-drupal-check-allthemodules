<?php

namespace Drupal\persian_fields\Plugin\Validation\Constraint;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IranMobileValidator extends ConstraintValidator {

  public function validate($value, Constraint $constraint) {
    if (!$this->isIranMobile($value)) {
      $this->context->addViolation(IranMobile::$message, []);
    }
  }

  /**
   * @param $value
   *
   * @return bool
   */
  private function isIranMobile($value) {

    if ((bool) preg_match('/^(((98)|(\+98)|(0098)|0)(9){1}[0-9]{9})+$/', $value) || (bool) preg_match('/^(9){1}[0-9]{9}+$/', $value)) {
      return TRUE;
    }

    return FALSE;
  }

}