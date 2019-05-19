<?php

namespace Drupal\sms_gateway_base;


class UnicodeHelper {

  /**
   * Converts a string to UCS-2 encoding if necessary.
   *
   * @param string $message
   *   The message string to be converted.
   *
   * @return string|false
   *   Returns the encoded string, or false if the convert function is not
   *   available.
   */
  public function convertToUnicode($message) {
    $hex1 = '';
    if (function_exists('iconv')) {
      $latin = @iconv('UTF-8', 'ISO-8859-1', $message);
      if (strcmp($latin, $message)) {
        $arr = unpack('H*hex', @iconv('UTF-8', 'UCS-2BE', $message));
        $hex1 = strtoupper($arr['hex']);
      }
      if ($hex1 == '') {
        $hex2 = '';
        $hex = '';
        for ($i = 0; $i < strlen($message); $i++) {
          $hex = dechex(ord($message[$i]));
          $len = strlen($hex);
          $add = 4 - $len;
          if ($len < 4) {
            for ($j = 0; $j < $add; $j++) {
              $hex = "0" . $hex;
            }
          }
          $hex2 .= $hex;
        }
        return $hex2;
      }
      else {
        return $hex1;
      }
    }
    else {
      return FALSE;
    }
  }

}
