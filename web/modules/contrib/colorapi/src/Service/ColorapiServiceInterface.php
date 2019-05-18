<?php

namespace Drupal\colorapi\Service;

/**
 * Interface for the Colorapi Service class.
 */
interface ColorapiServiceInterface {

  /**
   * Helper function to convert hex to rgb.
   *
   * @param string $hex
   *   The color in hexadecimal string format.
   * @param string $colorIndex
   *   The color value to return, must be one of:
   *   - red
   *   - green
   *   - blue.
   */
  public function hexToRgb($hex, $colorIndex);

}
