<?php

namespace Drupal\cpf;

/**
 * Defines the CpfService service, for CPF module.
 */
class CpfService {

  /**
   * Checks if the value you entered is a valid CPF number.
   *
   * @param string $value
   *   The value of the CPF number.
   *
   * @return bool
   *   Returns TRUE if the value entered is a valid CPF number. Otherwise,
   *   returns FALSE.
   */
  public function isValid($value) {
    $invalids = [
      '00000000000',
      '11111111111',
      '22222222222',
      '33333333333',
      '44444444444',
      '55555555555',
      '66666666666',
      '77777777777',
      '88888888888',
      '99999999999',
    ];

    $value = $this->digits($value);

    if (strlen($value) != 11 || in_array($value, $invalids)) {
      return FALSE;
    }
    else {
      for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
          $d += $value{$c} * (($t + 1) - $c);
        }

        $d = ((10 * $d) % 11) % 10;
        if ($value{$c} != $d) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

  /**
   * Generates a valid CPF number.
   */
  public function generate() {
    $cpf = '';

    $n = [
      rand(0, 9),
      rand(0, 9),
      rand(0, 9),
      rand(0, 9),
      rand(0, 9),
      rand(0, 9),
      rand(0, 9),
      rand(0, 9),
      rand(0, 9),
    ];

    $n[9] = $n[8] * 2 + $n[7] * 3 + $n[6] * 4 + $n[5] * 5 + $n[4] * 6;
    $n[9] += $n[3] * 7 + $n[2] * 8 + $n[1] * 9 + $n[0] * 10;
    $n[9] = 11 - ($n[9] % 11);
    $n[9] = $n[9] >= 10 ? 0 : $n[9];

    $n[10] = $n[9] * 2 + $n[8] * 3 + $n[7] * 4 + $n[6] * 5 + $n[5] * 6;
    $n[10] += $n[4] * 7 + $n[3] * 8 + $n[2] * 9 + $n[1] * 10 + $n[0] * 11;
    $n[10] = 11 - ($n[10] % 11);
    $n[10] = $n[10] >= 10 ? 0 : $n[10];

    $cpf = implode('', $n);
    return $cpf;
  }

  /**
   * Returns only the digits of a CPF number.
   */
  public function digits($value) {
    $digits = '';

    if (!empty($value)) {
      $digits = preg_replace("/[^0-9]/", "", $value);
    }

    return $digits;
  }

  /**
   * Returns only the digits of a CPF number.
   */
  public function mask($value) {
    $masked_value = '';
    $digits = $this->digits($value);

    if (!empty($digits)) {
      $mask = "%s%s%s.%s%s%s.%s%s%s-%s%s";
      $masked_value = vsprintf($mask, str_split($digits));
    }

    return $masked_value;
  }

}
