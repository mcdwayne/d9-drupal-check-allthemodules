<?php

namespace Drupal\field_nif;

use Drupal\Component\Utility\Unicode;

/**
 * Helper class to validate and extract document numbers.
 */
class NifUtils {

  /**
   * Helper function for validating NIF/CIF/NIE number.
   *
   * @param string $value
   *   NIF/CIF/NIE number to validate.
   * @param array $options
   *   List of valid document types to validate.
   *
   * @return array|bool
   *   Array of values if the number is correct, otherwise FALSE:
   *   - first_letter: First letter of the id number.
   *   - last_letter: Last letter of the id number.
   *   - number: Clean number (without letters).
   *   - type: NIF, CIF or NIE
   */
  public static function validateNifCifNie($value, $options = []) {
    $value = Unicode::strtoupper($value);
    $num = [];
    for ($i = 0; $i < 9; $i++) {
      $num[$i] = substr($value, $i, 1);
    }

    // Check the general format of the NIF/CIF/NIE.
    if (!preg_match('/((^[A-Z]{1}[0-9]{7}[A-Z0-9]{1}$|^[T]{1}[A-Z0-9]{8}$)|^[0-9]{8}[A-Z]{1}$)/', $value)) {
      return FALSE;
    }

    // Standard NIF numbers.
    if (in_array('nif', $options)) {
      if (preg_match('/(^[0-9]{8}[A-Z]{1}$)/', $value)) {
        if ($num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', substr($value, 0, 8) % 23, 1)) {
          $last_letter = $num[8];
          array_pop($num);
          return [
            'first_letter' => '',
            'last_letter' => $last_letter,
            'number' => implode('', $num),
            'type' => 'NIF',
          ];
        }
        else {
          return FALSE;
        }
      }
    }

    // CIF numbers.
    $sum = $num[2] + $num[4] + $num[6];
    for ($i = 1; $i < 8; $i += 2) {
      $sum += (int) substr((2 * $num[$i]), 0, 1) + (int) substr((2 * $num[$i]), 1, 1);
    }
    $n = 10 - (int) substr($sum, strlen($sum) - 1, 1);

    // Special NIF numbers that are calculated the same way as the CIF.
    if (in_array('nif', $options)) {
      if (preg_match('/^[KLM]{1}/', $value)) {
        if ($num[8] == chr(64 + $n)) {
          $last_letter = $num[8];
          $first_letter = $num[0];
          array_pop($num);
          array_shift($num);
          return [
            'first_letter' => $first_letter,
            'last_letter' => $last_letter,
            'number' => implode('', $num),
            'type' => 'NIF',
          ];
        }
        else {
          return FALSE;
        }
      }
    }

    // CIF number.
    if (in_array('cif', $options)) {
      if (preg_match('/^[ABCDEFGHJNPQRSUVW]{1}/', $value)) {
        if ($num[8] == chr(64 + $n) || $num[8] == substr($n, strlen($n) - 1, 1)) {
          if (!is_numeric($num[8])) {
            $last_letter = $num[8];
            array_pop($num);
          }
          $first_letter = $num[0];
          array_shift($num);
          return [
            'first_letter' => $first_letter,
            'last_letter' => (empty($last_letter)) ? '' : $last_letter,
            'number' => implode('', $num),
            'type' => 'CIF',
          ];
        }
        else {
          return FALSE;
        }
      }
    }

    // NIE check.
    if (in_array('nie', $options)) {
      // Values starting with T.
      if (preg_match('/^[T]{1}/', $value)) {
        if ($num[8] == preg_match('/^[T]{1}[A-Z0-9]{8}$/', $value)) {
          $last_letter = $num[8];
          $first_letter = $num[0];
          array_pop($num);
          array_shift($num);
          return [
            'first_letter' => $first_letter,
            'last_letter' => $last_letter,
            'number' => implode('', $num),
            'type' => 'NIE',
          ];
        }
        else {
          return FALSE;
        }
      }

      // Values starting with XYZ.
      if (preg_match('/^[XYZ]{1}/', $value)) {
        $last_letter = substr(str_replace(['X', 'Y', 'Z'], ['0', '1', '2'], $value), 0, 8) % 23;
        if ($num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', $last_letter, 1)) {
          $last_letter = $num[8];
          $first_letter = $num[0];
          array_pop($num);
          array_shift($num);
          return [
            'first_letter' => $first_letter,
            'last_letter' => $last_letter,
            'number' => implode('', $num),
            'type' => 'NIE',
          ];
        }
        else {
          return FALSE;
        }
      }
    }

    // If the number hasn't been validated yet, is not a valid NIF/CIF/NIE.
    return FALSE;
  }

}
