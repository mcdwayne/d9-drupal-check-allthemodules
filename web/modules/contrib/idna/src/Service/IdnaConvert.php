<?php

namespace Drupal\idna\Service;

use Algo26\IdnaConvert\ToIdn;
use Algo26\IdnaConvert\ToUnicode;

/**
 * IdnaConvert service.
 */
class IdnaConvert implements IdnaConvertInterface {

  /**
   * IdnaConvert Library Class.
   *
   * @var \Mso\IdnaConvert\IdnaConvert
   */
  protected $idna;

  /**
   * Construct.
   */
  public function __construct() {
    $this->idna = new ToIdn();
    $this->unicode = new ToUnicode();
  }

  /**
   * Encode.
   */
  public function encode($input) {
    if (strpos($input, 'xn--') === FALSE) {
      $input = $this->idna->convert($input);
    }
    return $input;
  }

  /**
   * Decode.
   */
  public function decode($input) {
    return $this->unicode->convert($input);
  }

}
