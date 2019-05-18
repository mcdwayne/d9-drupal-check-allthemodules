<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 29.01.17
 * Time: 12:24
 */

namespace Drupal\elastic_search\Utility;

/**
 * Class ElasticMappingDumper
 *
 * Basically totally based on Drupals own Drupal\Component\Utility\Variable
 * class but with the arrays printed out in a new school style
 *
 * @package Drupal\elastic_search\Utility
 */
class ElasticMappingDumper {

  /**
   * Drupal-friendly var_export().
   *
   * @param mixed  $var
   *   The variable to export.
   * @param string $prefix
   *   A prefix that will be added at the beginning of every lines of the
   *   output.
   *
   * @return string
   *   The variable exported in a way compatible to Drupal's coding standards.
   */
  public static function export($var, $prefix = '') {
    if (is_array($var)) {
      if (empty($var)) {
        $output = '[]';
      } else {
        $output = "[\n";
        // Don't export keys if the array is non associative.
        $export_keys = array_values($var) != $var;
        foreach ($var as $key => $value) {
          $output .= '  ' .
                     ($export_keys ? static::export($key) . ' => ' : '') .
                     static::export($value, '  ', FALSE) . ",\n";
        }
        $output .= ']';
      }
    } elseif (is_bool($var)) {
      $output = $var ? 'TRUE' : 'FALSE';
    } elseif (is_string($var)) {
      if (strpos($var, "\n") !== FALSE || strpos($var, "'") !== FALSE) {
        // If the string contains a line break or a single quote, use the
        // double quote export mode. Encode backslash, dollar symbols, and
        // double quotes and transform some common control characters.
        $var = str_replace(['\\', '$', '"', "\n", "\r", "\t"],
                           ['\\\\', '\$', '\"', '\n', '\r', '\t'],
                           $var);
        $output = '"' . $var . '"';
      } else {
        $output = "'" . $var . "'";
      }
    } elseif (is_object($var) && get_class($var) === 'stdClass') {
      // var_export() will export stdClass objects using an undefined
      // magic method __set_state() leaving the export broken. This
      // workaround avoids this by casting the object as an array for
      // export and casting it back to an object when evaluated.
      $output = '(object) ' . static::export((array) $var, $prefix);
    } else {
      $output = var_export($var, TRUE);
    }

    if ($prefix) {
      $output = str_replace("\n", "\n$prefix", $output);
    }

    return $output;
  }

}
