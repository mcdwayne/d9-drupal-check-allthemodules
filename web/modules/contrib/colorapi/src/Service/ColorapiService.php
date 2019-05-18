<?php

namespace Drupal\colorapi\Service;

use Drupal\colorapi\Plugin\DataType\HexColorInterface;

/**
 * Service class for the Color API module.
 */
class ColorapiService implements ColorapiServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function isValidHexadecimalColorString($string) {
    return preg_match(HexColorInterface::HEXADECIMAL_COLOR_REGEX, $string);
  }

  /**
   * {@inheritdoc}
   */
  public function hexToRgb($hex, $colorIndex) {
    $hex = str_replace("#", "", $hex);

    if ($colorIndex == 'red') {
      if (strlen($hex) == 3) {
        $rgb_value = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
      }
      else {
        $rgb_value = hexdec(substr($hex, 0, 2));
      }
    }
    elseif ($colorIndex == 'green') {
      if (strlen($hex) == 3) {
        $rgb_value = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
      }
      else {
        $rgb_value = hexdec(substr($hex, 2, 2));
      }
    }
    if ($colorIndex == 'blue') {
      if (strlen($hex) == 3) {
        $rgb_value = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
      }
      else {
        $rgb_value = hexdec(substr($hex, 4, 2));
      }
    }

    return $rgb_value;
  }

}
