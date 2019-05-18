<?php

namespace Drupal\commerce_wayforpay\Helpers;

/**
 * Class Arr helper.
 *
 * @package Drupal\commerce_wayforpay\Helpers
 */
class Arr {

  /**
   * Get value form array.
   *
   * @param array $arr
   *   Input array.
   * @param string $key
   *   Key.
   * @param mixed $default
   *   Default value.
   *
   * @return mixed
   *   Value.
   */
  public static function get(array $arr, $key, $default = NULL) {
    return isset($arr[$key]) ? $arr[$key] : $default;
  }

  /**
   * Extract values by keys.
   *
   * @param array $arr
   *   Input array.
   * @param array $keys
   *   Search keys.
   *
   * @return array
   *   Values.
   */
  public static function extract(array $arr, array $keys) {
    $result = [];
    foreach ($keys as $key) {
      if (isset($arr[$key])) {
        $result[$key] = $arr[$key];
      }
    }
    return $result;
  }

}
