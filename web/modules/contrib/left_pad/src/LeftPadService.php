<?php

/**
 * @file
 * Contains \Drupal\left_pad\LeftPadService.
 */

namespace Drupal\left_pad;

/**
 * Class LeftPadService.
 *
 * @package Drupal\left_pad
 */
class LeftPadService implements LeftPadServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function leftPad($str, $len, $chr) {
    return str_pad($str, $len, $chr, STR_PAD_LEFT);
  }

}
