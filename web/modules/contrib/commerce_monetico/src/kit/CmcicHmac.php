<?php

namespace Drupal\commerce_cmcic\kit;

use Drupal\Component\Utility\Unicode;

/**
 * This class allows to manipulate the MAC code.
 */
class CmcicHmac {

  // The usable TPE key.
  protected $sUsableKey;

  /**
   * Constructor class.
   *
   * @param object $tpe
   *   The TPE object.
   */
  public function __construct($tpe) {
    $this->sUsableKey = $this->getUsableKey($tpe);
  }

  /**
   * Get the key to be used for MAC generation.
   *
   * @param object $tpe
   *   The TPE object.
   *
   * @return string
   *   The key ready to be used.
   */
  protected function getUsableKey($tpe) {

    $hex_str_key = Unicode::substr($tpe->getCle(), 0, 38);
    $hex_final = "" . Unicode::substr($tpe->getCle(), 38, 2) . "00";

    $cca0 = ord($hex_final);

    if ($cca0 > 70 && $cca0 < 97) {
      $hex_str_key .= chr($cca0 - 23) . Unicode::substr($hex_final, 1, 1);
    }
    else {
      if (Unicode::substr($hex_final, 1, 1) == "M") {
        $hex_str_key .= Unicode::substr($hex_final, 0, 1) . "0";
      }
      else {
        $hex_str_key .= Unicode::substr($hex_final, 0, 2);
      }
    }

    return pack("H*", $hex_str_key);

  }

  /**
   * Allows to generate the MAC seal.
   *
   * @param string $data
   *   The data to compute.
   *
   * @return string
   *   The HMAC.
   */
  public function computeHmac($data) {
    return Unicode::strtolower(hash_hmac("sha1", $data, $this->sUsableKey));
  }

}
