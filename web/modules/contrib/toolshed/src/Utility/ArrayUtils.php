<?php

namespace Drupal\toolshed\Utility;

/**
 * Generic helper methods for use with PHP Arrays.
 */
class ArrayUtils {

  /**
   * Apply a string prefix to all strings in an array.
   *
   * @param string[] $items
   *   Array of string values to append a string suffix to.
   * @param string $prefix
   *   The string prefix to add to each value in the array.
   *
   * @return array
   *   The array items after they have been prefixed, with
   *   the $prefix value.
   */
  public static function prefix(array $items, $prefix) {
    foreach ($items as &$item) {
      $item = $prefix . $item;
    }
    return $items;
  }

  /**
   * Apply a string suffix to all strings in an array.
   *
   * @param string[] $items
   *   Array of string values to append a string suffix to.
   * @param string $suffix
   *   The string suffix to add to each value in the array.
   *
   * @return array
   *   The array of items have the suffix has been applied to
   *   all values of the original array.
   */
  public static function suffix(array $items, $suffix) {
    foreach ($items as &$item) {
      $item .= $suffix;
    }
    return $items;
  }

}
