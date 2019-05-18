<?php

namespace Drupal\persian_fields\Plugin\Validation\Constraint;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MelliCodeValidator extends ConstraintValidator {

  public function validate($value, Constraint $constraint) {
    if (!$this->isMelliCode($value)) {
      $this->context->addViolation(MelliCode::$message, []);
    }

  }

  /**
   * @param $value
   *
   * @return bool
   */
  private function isMelliCode($value) {
    if (!preg_match('/^\d{8,10}$/', $value) || preg_match('/^[0]{10}|[1]{10}|[2]{10}|[3]{10}|[4]{10}|[5]{10}|[6]{10}|[7]{10}|[8]{10}|[9]{10}$/', $value)) {
      return FALSE;
    }

    $sub = 0;

    if (strlen($value) == 8) {
      $value = '00' . $value;
    }
    elseif (strlen($value) == 9) {
      $value = '0' . $value;
    }

    for ($i = 0; $i <= 8; $i++) {
      $sub = $sub + ($value[$i] * (10 - $i));
    }

    if (($sub % 11) < 2) {
      $control = ($sub % 11);
    }
    else {
      $control = 11 - ($sub % 11);
    }

    if ($value[9] == $control) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}