<?php

namespace Drupal\drupal_inquicker\traits;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Utility\Error;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * General utilities trait.
 *
 * If your class needs to use any of these, add "use CommonUtilities" your class
 * and these methods will be available and mockable in tests.
 */
trait CommonUtilities {

  /**
   * Mockable wrapper around \Drupal::config()->get().
   */
  public function configGet($variable, $default = NULL) {
    $return = \Drupal::config('drupal_inquicker')->get($variable, $default);
    return ($return === NULL) ? $default : $return;
  }

  /**
   * Get configuration as an array.
   */
  public function configGetArray($variable, $default = []) : array {
    return $this->configGet($variable, $default);
  }

  /**
   * Log a string to the watchdog and return a UUID.
   *
   * @param string $string
   *   String to be logged.
   */
  public function errorUuid(string $string) : string {
    $uuid = $this->generateUuid();
    $string2 = $uuid . ' ' . $string;
    $this->watchdog($string2);
    return $uuid;
  }

  /**
   * Generate a UUID.
   *
   * @return string
   *   A generated UUID.
   */
  public function generateUuid() : string {
    return \Drupal::service('uuid')->generate();
  }

  /**
   * Mockable wrapper around \Drupal::httpClient()->get().
   */
  public function httpGet($uri, $options = []) {
    $this->watchdog('Making request to ' . $uri . ' with the following options:');
    $this->watchdog(serialize($options));
    return \Drupal::httpClient()->get($uri, $options);
  }

  /**
   * Mockable wrapper around \Drupal::moduleHandler()->invokeAll().
   */
  public function invokeHook(string $name, array $args) : array {
    return \Drupal::moduleHandler()->invokeAll($name, $args);
  }

  /**
   * Mockable wrapper around Json::decode().
   */
  public function jsonDecode(string $json) {
    return Json::decode($json);
  }

  /**
   * Mockable wrapper around Json::encode().
   */
  public function jsonEncode($data) : string {
    return Json::encode($data);
  }

  /**
   * Mockable wrapper around \Drupal::state()->get().
   */
  public function stateGet($variable, $default = NULL) {
    return \Drupal::state()->get($variable, $default);
  }

  /**
   * Mockable wrapper around \Drupal::state()->set().
   */
  public function stateSet($variable, $value) {
    \Drupal::state()->set($variable, $value);
  }

  /**
   * Mockable wrapper around t().
   *
   * See that function for details.
   */
  public function t($string, array $args = [], array $options = []) {
    // @codingStandardsIgnoreStart
    return t($string, $args, $options);
    // @codingStandardsIgnoreEnd
  }

  /**
   * Validate that data is an object of the expected class.
   *
   * An exception is thrown if the data does not validate.
   *
   * @param mixed $data
   *   An object.
   * @param string $expected
   *   A class name.
   */
  public function validateClass($data, $expected) {
    $class = get_class($data);
    if ($class != $expected) {
      throw new \Exception($class . ' is not ' . $expected);
    };
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
    \Drupal::logger('drupal_inquicker')->notice($string);
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
    \Drupal::logger('drupal_inquicker')->error($string);
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

    \Drupal::logger('drupal_inquicker')->log($severity, $message, $variables);
  }

}
