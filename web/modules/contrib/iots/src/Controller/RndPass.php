<?php

namespace Drupal\iots\Controller;

/**
 * @file
 * Contains \Drupal\iots\Controller\RndPass.
 */

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller RndPass.
 */
class RndPass extends ControllerBase {

  /**
   * Gen.
   */
  public static function gen($length = 8) {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array();
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < $length; $i++) {
      $n = rand(0, $alphaLength);
      $pass[] = $alphabet[$n];
    }
    return implode($pass);
  }

}
