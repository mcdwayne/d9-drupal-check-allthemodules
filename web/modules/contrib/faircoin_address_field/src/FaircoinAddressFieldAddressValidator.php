<?php
/**
 * @file
 * Class FaircoinAddressFieldAddressValidator to validate a faircoin address.
 */

namespace Drupal\faircoin_address_field;

use Drupal\Component\Utility\Unicode;

/**
 * Class for validating a faircoin address.
 */
class FaircoinAddressFieldAddressValidator {
  /**
   * Validate faircoin address.
   */
  static public function checkAddress($address) {
    if (preg_match('/[^1-9A-HJ-NP-Za-km-z]/', $address)) {
      return FALSE;
    }
    // Decode address.
    $hexadecimal = '0123456789ABCDEF';
    $base58 = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    $raw = "0";
    for ($i = 0; $i < Unicode::strlen($address); $i++) {
      $position = (string) strpos($base58, $address[$i]);
      $raw = (string) bcmul($raw, "58", 0);
      $raw = (string) bcadd($raw, $position, 0);
    }
    $hex = "";
    while (bccomp($raw, 0) == 1) {
      $dv = (string) bcdiv($raw, "16", 0);
      $rem = (integer) bcmod($raw, "16");
      $raw = $dv;
      $hex = $hex . $hexadecimal[$rem];
    }
    $addr_decoded = strrev($hex);
    // Amend padding.
    for ($i = 0; $i < Unicode::strlen($address) && $address[$i] == "1"; $i++) {
      $addr_decoded = "00" . $addr_decoded;
    }
    if (Unicode::strlen($addr_decoded) % 2 != 0) {
      $addr_decoded = "0" . $addr_decoded;
    }
    // Control invalid length.
    if (Unicode::strlen($addr_decoded) != 50) {
      return FALSE;
    }
    // Control invalid type.
    // FairCoin main net public key: "5F"
    // FairCoin main net script: "24"
    $type = Unicode::substr($addr_decoded, 0, 2);
    if ($type != "5F" && $type != "24") {
      return FALSE;
    }
    // Check address.
    $ch = Unicode::substr($addr_decoded, 0, Unicode::strlen($addr_decoded) - 8);
    $ch = pack("H*", $ch);
    $ch = hash("sha256", $ch, TRUE);
    $ch = hash("sha256", $ch);
    $ch = Unicode::strtoupper($ch);
    $ch = Unicode::substr($ch, 0, 8);
    $is_valid = ($ch == Unicode::substr($addr_decoded, Unicode::strlen($addr_decoded) - 8));
    return ($is_valid ? $type : FALSE);
  }

}
