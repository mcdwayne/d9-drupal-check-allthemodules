<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 13/01/17
 * Time: 13:12
 */

namespace Drupal\elastic_search\Utility;

/**
 * Class ArrayKeyToCamelCaseHelper
 *
 * @package Drupal\elastic_search\Utility
 */
class ArrayKeyToCamelCaseHelper {

  /**
   * @param array $data
   * @param bool  $recurse
   * @param array $ids
   *
   * @return array
   */
  public function convert(array $data,
                          bool $recurse = FALSE,
                          array $ids = []) {

    $converted = [];
    $this->doConvert($converted, $data, $recurse, $ids);
    return $converted;

  }

  /**
   * @param array $converted
   * @param array $data
   * @param bool  $recurse
   * @param array $ids
   */
  private function doConvert(array &$converted,
                             array $data,
                             bool $recurse = FALSE,
                             array $ids = []) {

    foreach ($data as $id => $value) {

      if (empty($ids) || array_key_exists($id, $ids)) {
        $id = $this->dashesToCamelCase($id);
      }

      if ($recurse && is_array($value)) {
        $converted[$id] = [];
        $this->doConvert($converted[$id], $value, TRUE, $ids);
      } else {
        $converted[$id] = $value;
      }

    }
  }

  /**
   * @param string $string
   *
   * @return mixed
   */
  private function dashesToCamelCase(string $string) {
    return preg_replace_callback("/_[a-zA-Z]/",
                                 [$this, 'removeDashAndCapitalize'],
                                 $string);
  }

  /**
   * @param array $matches
   *
   * @return string
   */
  private function removeDashAndCapitalize(array $matches) {
    return strtoupper($matches[0][1]);
  }

}