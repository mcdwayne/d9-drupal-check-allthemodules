<?php

namespace Drupal\persian_fields\Plugin\Validation\Constraint;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ShebaValidator extends ConstraintValidator {

  public function validate($value, Constraint $constraint) {
    if (!$this->isSheba($value)) {
      $this->context->addViolation(Sheba::$message, []);
    }

  }

  /**
   * @param $value
   *
   * @return bool
   */
  private function isSheba($value) {
    $ibanReplaceValues = [];

    if (!empty($value)) {
      $value = preg_replace('/[\W_]+/', '', strtoupper($value));

      if ((4 > strlen($value) || strlen($value) > 34) || (is_numeric($value [0]) || is_numeric($value [1])) || (!is_numeric($value [2]) || !is_numeric($value [3]))) {
        return FALSE;
      }

      $ibanReplaceChars = range('A', 'Z');

      foreach (range(10, 35) as $tempvalue) {
        $ibanReplaceValues[] = strval($tempvalue);
      }


      $tmpIBAN = substr($value, 4) . substr($value, 0, 4);

      $tmpIBAN = str_replace($ibanReplaceChars, $ibanReplaceValues, $tmpIBAN);

      $tmpValue = intval(substr($tmpIBAN, 0, 1));

      for ($i = 1; $i < strlen($tmpIBAN); $i++) {
        $tmpValue *= 10;

        $tmpValue += intval(substr($tmpIBAN, $i, 1));

        $tmpValue %= 97;
      }

      if ($tmpValue != 1) {
        return FALSE;
      }

      return TRUE;
    }

    return FALSE;
  }

}