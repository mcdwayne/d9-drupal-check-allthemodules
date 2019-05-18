<?php

namespace Drupal\commerce_webpay_by\Common;

/**
 * Helper class to implement additional functionality.
 */
class Helper {

  /**
   * Extract a value from an array, by the key.
   *
   * @param string $name
   *   The name of key.
   * @param array $array
   *   Array to find value from.
   *
   * @return mixed
   *   Found value.
   *
   * @throws \OutOfBoundsException
   */
  public static function fetchArrayValueByKey(string $name, array $array) {
    if (isset($array[$name])) {
      return $array[$name];
    }
    throw new \OutOfBoundsException("There is no item \"{$name}\" in the array!");
  }

}
