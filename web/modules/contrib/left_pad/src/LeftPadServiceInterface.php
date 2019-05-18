<?php

/**
 * @file
 * Contains \Drupal\left_pad\LeftPadServiceInterface.
 */

namespace Drupal\left_pad;

/**
 * Interface LeftPadServiceInterface.
 *
 * @package Drupal\left_pad
 */
interface LeftPadServiceInterface {

  /**
   * @param string $str
   *   The string.
   * @param int $len
   *   The length to pad the string to.
   * @param string $ch
   *   The character to use for padding.
   *
   * @return mixed
   */
  public function leftPad($str, $len, $ch);

}
