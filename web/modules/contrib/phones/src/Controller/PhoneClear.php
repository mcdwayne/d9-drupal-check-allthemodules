<?php

namespace Drupal\phones\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for page example routes.
 */
class PhoneClear extends ControllerBase {

  /**
   * Clear.
   */
  public static function clear($phone) {
    $phone = str_replace(["+", "-"], "", $phone);
    $phone = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
    if (strlen($phone) == 11) {
      if (substr($phone, 0, 1) == 8) {
        $phone = "7" . substr($phone, 1);
      }
      if (substr($phone, 0, 4) == '7800') {
        $phone = FALSE;
      }
    }
    else {
      $phone = FALSE;
    }
    return $phone;
  }

}
