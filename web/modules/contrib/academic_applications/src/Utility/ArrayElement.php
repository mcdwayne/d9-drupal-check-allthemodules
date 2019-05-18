<?php

namespace Drupal\academic_applications\Utility;

/**
 * Utilities for manipulating Webform array element data.
 */
class ArrayElement {

  /**
   * Converts an array result to a string.
   *
   * @param array $result
   *   An array submission result.
   *
   * @return string
   *   A concatenated string.
   */
  public static function resultToString(array $result) {
    $strings = [];
    foreach ($result as $machine_name => $value) {
      $strings[] = "$machine_name: $value";
    }
    return implode(', ', $strings);
  }

}
