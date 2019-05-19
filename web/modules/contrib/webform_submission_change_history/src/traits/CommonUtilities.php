<?php

namespace Drupal\webform_submission_change_history\traits;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Utility\Error;

/**
 * General utilities trait.
 *
 * If your class needs to use any of these, add "use CommonUtilities" your class
 * and these methods will be available and mockable in tests.
 */
trait CommonUtilities {

  /**
   * Get the data store singleton.
   *
   * @return DataStore
   *   The DataStore singleton.
   */
  public function dataStore(): DataStore {
    return DataStore::instance();
  }

  /**
   * Mockable wrapper around microtime().
   */
  public function microtime(bool $get_as_float = FALSE) {
    return microtime($get_as_float);
  }

  /**
   * Insert an array after a key.
   *
   * See https://stackoverflow.com/a/40305210/1207752.
   *
   * @param array $array
   *   The initial array.
   * @param string $key
   *   The key after which to insert another array.
   * @param array $array_to_insert
   *   The array to insert.
   *
   * @return array
   *   The modified array.
   */
  public function spliceAfterKey(array $array, string $key, array $array_to_insert) : array {
    $key_pos = array_search($key, array_keys($array));
    if ($key_pos !== FALSE && !empty($array[$key]['#title'])) {
      $key_pos++;
      $second_array = array_splice($array, $key_pos);
      $array = array_merge($array, $array_to_insert, $second_array);
    }
    else {
      foreach (array_keys($array) as $id) {
        if (is_array($array[$id])) {
          $array[$id] = $this->spliceAfterKey($array[$id], $key, $array_to_insert);
        }
      }
    }
    return $array;
  }

  /**
   * Log a string to the watchdog.
   *
   * @param string $string
   *   String to be logged.
   *
   * @throws Exception
   */
  public function watchdog(string $string) {
    \Drupal::logger('steward_common')->notice($string);
  }

  /**
   * Log an error to the watchdog.
   *
   * @param string $string
   *   String to be logged.
   *
   * @throws Exception
   */
  public function watchdogError(string $string) {
    \Drupal::logger('steward_common')->error($string);
  }

  /**
   * Log a \Throwable to the watchdog.
   *
   * @param \Throwable $t
   *   A \throwable.
   */
  public function watchdogThrowable(\Throwable $t, $message = NULL, $variables = array(), $severity = RfcLogLevel::ERROR, $link = NULL) {

    // Use a default value if $message is not set.
    if (empty($message)) {
      $message = '%type: @message in %function (line %line of %file).';
    }

    if ($link) {
      $variables['link'] = $link;
    }

    $variables += Error::decodeException($t);

    \Drupal::logger('steward_common')->log($severity, $message, $variables);
  }

  /**
   * Mockable wrapper around fopen().
   */
  public function fopen(string $filename, string $mode) {
    $return = fopen($filename, $mode);
    if (!$return) {
      throw new \Exception('fopen() returned FALSE');
    }
    return $return;
  }

  /**
   * Mockable wrapper around drupal_get_path().
   */
  public function drupalGetPath(string $type, string $name) : string {
    $return = drupal_get_path($type, $name);
    if (!$return) {
      throw new \Exception('drupal_get_path() returned an empty string');
    }
    return $return;
  }

  /**
   * Mockable wrapper around fgetcsv().
   */
  public function fgetcsv($handle) {
    $return = fgetcsv($handle);
    if ($return === NULL) {
      throw new \Exception('fgetcsv() failure');
    }
    return $return;
  }

  /**
   * Mockable wrapper around fclose().
   */
  public function fclose($handle) {
    $return = fclose($handle);
    if (!$return) {
      throw new \Exception('fclose() failure');
    }
    return $return;
  }

  /**
   * Mockable wrapper around \Drupal::currentUser()->hasPermission().
   */
  public function userAccess(string $perm) : bool {
    return \Drupal::currentUser()->hasPermission($perm);
  }

}
