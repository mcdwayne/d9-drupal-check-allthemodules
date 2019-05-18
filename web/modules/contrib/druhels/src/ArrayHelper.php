<?php

namespace Drupal\druhels;

class ArrayHelper {
  /**
   * Remove empty elements from array.
   *
   * @param array $array Source array
   * @return array
   */
  public static function removeEmptyElements(array $array) {
    foreach ($array as $key => $value) {
      if (!$value) {
        unset($array[$key]);
      }
    }

    return $array;
  }

  /**
   * Rename keys to column value.
   *
   * @param array $array Source array
   * @param mixed $column_key Column key
   * @return array
   */
  public static function renameKeysToColumnValue(array $array, $column_key) {
    $new_array = array();
    foreach ($array as $key => $values) {
      if (isset($values[$column_key])) {
        $new_array[$values[$column_key]] = $values;
      }
    }
    return $new_array;
  }

  /**
   * Insert element before/after key.
   *
   * @param array $array Input array
   * @param integer|string $key Array key
   * @param array $value Element
   * @param string $insert_type Insertion type 'before' or 'after'
   * @return array
   */
  public static function insert($array, $key, $value, $insert_type) {
    $key_position = array_search($key, array_keys($array), TRUE);

    if ($key_position !== FALSE) {
      $offset = ($insert_type == 'after') ? 1 : 0;
      $array = array_slice($array, 0, $key_position + $offset, TRUE)
        + $value
        + array_slice($array, $key_position + $offset, count($array), TRUE);
    }
    else {
      $array += $value;
    }

    return $array;
  }

  /**
   * Insert element before key.
   *
   * @param array $array Input array
   * @param integer|string $key Array key
   * @param array $value Element
   * @return array
   */
  public static function insertBefore($array, $key, $value) {
    return self::insert($array, $key, $value, 'before');
  }

  /**
   * Insert element after key.
   *
   * @param array $array Input array
   * @param integer|string $key Array key
   * @param array $value Element
   * @return array
   */
  public static function insertAfter($array, $key, $value) {
    return self::insert($array, $key, $value, 'after');
  }

  /**
   * Rename key.
   *
   * @param array $array Input array
   * @param mixed $old_key Old key name
   * @param mixed $new_key New key name
   * @return array
   */
  public static function renameKey(&$array, $old_key, $new_key) {
    if (isset($array[$old_key])) {
      $array = self::insertAfter($array, $old_key, array($new_key => $array[$old_key]));
      unset($array[$old_key]);
    }
    return $array;
  }

  /**
   * Remove elements from array by keys.
   *
   * @param array $array Input array
   * @param array $keys Array of keys for remove
   * @return array
   */
  public static function removeElementsByKey($array, $keys) {
    foreach ($keys as $key) {
      if (array_key_exists($key, $array)) {
        unset($array[$key]);
      }
    }

    return $array;
  }

  /**
   * Remove elements from $array which one keys are not in $keys.
   *
   * @param array $array Input array
   * @param array $keys Array of keys
   * @return array
   */
  public static function removeExtraElements($array, $keys) {
    return array_intersect_key($array, array_fill_keys($keys, NULL));
  }

  /**
   * Return text lines in array.
   *
   * @param string $text Text
   * @return array
   */
  public static function getTextLines($text, $remove_empty = TRUE) {
    $lines = preg_split("/\r\n|\n|\r/", $text);

    if ($remove_empty) {
      $lines = self::removeEmptyElements($lines);
    }

    return $lines;
  }

  /**
   * Search in two-dimensional array.
   *
   * @param array $array Two-dimensional array
   * @param mixed $key Second dimension key
   * @param mixed $value Search value
   * @return mixed|FALSE Key
   */
  public static function searchInTwodimArray($array, $key, $value, $strict = FALSE) {
    foreach ($array as $array_key => $second_array) {
      if (array_key_exists($key, $second_array)) {
        if ($strict) {
          if ($second_array[$key] == $value) {
            return $array_key;
          }
        }
        elseif ($second_array[$key] === $value) {
          return $array_key;
        }
      }
    }

    return FALSE;
  }

  /**
   * Return first key.
   */
  public static function getFirstKey($array) {
    reset($array);
    return key($array);
  }

  /**
   * Return last key.
   */
  public static function getLastKey($array) {
    end($array);
    return key($array);
  }

  /**
   * Sort array by second array orders.
   */
  public static function sortBySecondArrayValues($array, $second_array) {
    $sorted_array = array();

    foreach ($second_array as $value) {
      if (array_key_exists($value, $array)) {
        $sorted_array[$value] = $array[$value];
      }
    }

    $sorted_array += $array;

    return $sorted_array;
  }
}
