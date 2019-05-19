<?php

namespace Drupal\unique_code_field;

use PragmaRX\Random\Random;

/**
 * Code generator for Unique Code Field.
 *
 * @author Alessandro Cereda <alessandro@geekworldesign.com>
 */
class Generator {

  /**
   * PragmaRX class variable.
   *
   * @var PragmaRX\Random\Random
   */
  protected $random;

  /**
   * Inject the Random class in protected var.
   */
  public function __construct() {
    $this->random = new Random();
  }

  /**
   * Generate an alphabetical code.
   *
   * @param int $size
   *   The size of the code we are generating.
   *
   * @return string
   *   The code.
   */
  private function generateAlphabetical(int $size) {
    $code = $this->random->pattern('[A-Za-z]')->size($size)->get();
    return $code;
  }

  /**
   * Generate an alphanumeric code.
   *
   * @param int $size
   *   The size of the code we are generating.
   *
   * @return string
   *   The code.
   */
  private function generateAlphanumerical(int $size) {
    $code = $this->random->alpha()->size($size)->get();
    return $code;
  }

  /**
   * Generate a numeric code.
   *
   * @param int $size
   *   The size of the code we are generating.
   *
   * @return string
   *   The code.
   */
  private function generateNumeric(int $size) {
    $code = $this->random->numeric()->size($size)->get();
    return $code;
  }

  /**
   * Generate a unique code.
   *
   * @param string $type
   *   The type of the code you want to generate.
   * @param int $length
   *   The size of the code you want to generate.
   *
   * @return null|string
   *   The generated code. NULL if the requested type does not exists.
   */
  public function generateCode(string $type, int $length) {
    switch ($type) {
      case 'alphabetical':
        $code = $this->generateAlphabetical($length);
        break;

      case 'alphanumeric':
        $code = $this->generateAlphanumerical($length);
        break;

      case 'numeric':
        $code = $this->generateNumeric($length);
        break;

      default:
        $code = NULL;
        break;
    }
    return $code;
  }

}
