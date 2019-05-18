<?php

namespace Drupal\chartjs_api;

/**
 * Class ChartjsApiUtils.
 *
 * @package Drupal\chartjs_api
 */
class ChartjsApiUtils {

  /**
   * Darken hexadecimal color.
   *
   * @param string $hex
   *   Color to darken.
   * @param int $steps
   *   Percentage to be darkened: -255 and 255.
   *   Negative = darker, positive = lighter.
   *
   * @return string
   *   Hexadecimal color.
   */
  public static function darkenColor($hex, $steps) {
    // Steps should be between -255 and 255.
    // Negative = darker, positive = lighter.
    $steps = max(-255, min(255, $steps));

    // Normalize into a six character long hex string.
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
      $hex = str_repeat(substr($hex, 0, 1), 2)
             . str_repeat(substr($hex, 1, 1), 2)
             . str_repeat(substr($hex, 2, 1), 2);
    }

    // Split into three parts: R, G and B.
    $color_parts = str_split($hex, 2);
    $return = '#';

    foreach ($color_parts as $color) {
      // Convert to decimal.
      $color = hexdec($color);
      // Adjust color.
      $color = max(0, min(255, $color + $steps));
      // Make two char hex code.
      $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT);
    }

    return $return;
  }

}
