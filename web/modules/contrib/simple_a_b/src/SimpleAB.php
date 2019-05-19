<?php

namespace Drupal\simple_a_b;

/**
 * Custom class to create test for simple_a_b.
 */
class SimpleAB {

  /**
   * Uses the test object data to calculate version to show.
   *
   * If cookies are set we make sure we only show the
   * same one for the set amount of time.
   *
   * @param object $test_obj
   *   The test object data.
   *
   * @return bool
   *   Returns response state
   */
  public static function calculateExperience($test_obj) {
    // Get the remember state.
    $getRemember = self::getRemember($test_obj);

    // If we have a remember value, and the value is "true" or "false".
    // Convert to bool & set as the response.
    if ($getRemember && ($getRemember === "true" || $getRemember === "false")) {
      $response = $getRemember === "true" ? TRUE : FALSE;
    }
    else {
      // Otherwise calculate the response data.
      $response = self::calculateVariation($test_obj);

      // Set a new remember state.
      self::setRemember($test_obj, $response);
    }

    // Send the data over to the reporter.
    self::report($test_obj, $response);

    return $response;
  }

  /**
   * Calculates the experience for this user or session.
   *
   * Currently this just uses a random number gen this will at some point
   * be updated to be smarter and more useful.
   * TODO: Make this more smart and useful.
   *
   * @param object $test_obj
   *   The test object data.
   *
   * @return bool
   *   Returns true of false
   */
  public static function calculateVariation($test_obj) {
    // Otherwise calculate the response data.
    $num = rand(1, 100);
    $response = $num > 49 ? TRUE : FALSE;

    return $response;
  }

  /**
   * Start the process of sending data over to reporting module.
   *
   * @param object $test_obj
   *   The test object data.
   * @param bool $response
   *   The response of the variation.
   */
  public static function report($test_obj, $response) {
    // Load the simple a/b settings.
    $simple_a_b_config = \Drupal::config('simple_a_b.settings');
    // Het the status for reporting methods.
    $reportMethod = $simple_a_b_config->get('reporting');

    // If we have a reporting method and that reporting method is not "_none",.
    if ($reportMethod && $reportMethod !== "_none") {
      // Load up the plugin manger.
      $manager = \Drupal::service('plugin.manager.simpleab.report');
      // Load the instance for the selected plugin.
      $instance = $manager->createInstance($reportMethod);
      // Grab its reporting method.
      $method = $instance->getReportingMethod();

      // Call its reporting method.
      // Passing in the object data and the response status.
      $method($test_obj, $response);
    }
  }

  /**
   * Set the remember state.
   *
   * @param object $obj
   *   The test object data.
   * @param bool $value
   *   The response of the variation.
   *
   * @return bool|int
   *   Returns true/false or -1 if failed
   */
  public static function setRemember($obj, $value) {
    // Load the simple a/b settings.
    $simple_a_b_config = \Drupal::config('simple_a_b.settings');
    // Get the status for remember method.
    $rememberMethod = $simple_a_b_config->get('remember');

    // If $rememberMethod is set and is not "_none".
    if ($rememberMethod && $rememberMethod !== "_none") {
      // Get the prefix.
      $prefix = $simple_a_b_config->get('remember_prefix');
      // Create slug key.
      $key = self::slugify($prefix . "-" . $obj->name . "-" . $obj->tid);
      // Get lifetime value.
      $lifetime = (int) $simple_a_b_config->get('remember_lifetime');
      // Get current request time.
      $request_time = \Drupal::time()->getRequestTime();
      // Convert true/false to strings.
      $value = ($value) ? 'true' : 'false';

      // Switch case on the method.
      switch ($rememberMethod) {

        // If cookie, create a new cookie setting the value and lifetime.
        case 'cookie':
          return setcookie($key, $value, $request_time + $lifetime);
      }
    }

    // If we failed return -1.
    return -1;
  }

  /**
   * Gets a remember state.
   *
   * @param object $obj
   *   The test object data.
   *
   * @return int
   *   Returns true/false or -1 if failed
   */
  public static function getRemember($obj) {
    // Load the simple a/b settings.
    $simple_a_b_config = \Drupal::config('simple_a_b.settings');
    // Get the status for remember method.
    $rememberMethod = $simple_a_b_config->get('remember');

    // If $rememberMethod is set and is not "_none".
    if ($rememberMethod && $rememberMethod !== "_none") {
      // Get the prefix.
      $prefix = $simple_a_b_config->get('remember_prefix');
      // Create slug key.
      $key = self::slugify($prefix . "-" . $obj->name . "-" . $obj->tid);

      // Switch case on the method.
      switch ($rememberMethod) {

        // If cookie, try and get then return the value.
        case 'cookie':
          return $_COOKIE[$key];
      }
    }

    // If we failed return -1.
    return -1;
  }

  /**
   * Slugify method.
   *
   * Https://stackoverflow.com/questions/2955251/php-function-to-make-slug-url-string.
   *
   * @param string $string
   *   The string to be slugify.
   *
   * @return string
   *   Returns a slugified strring
   */
  public static function slugify($string) {
    $string = preg_replace('~[^\pL\d]+~u', '-', $string);
    $string = iconv('utf-8', 'us-ascii//TRANSLIT', $string);
    $string = preg_replace('~[^-\w]+~', '', $string);
    $string = trim($string, '-');
    $string = preg_replace('~-+~', '-', $string);
    $string = strtolower($string);

    if (empty($string)) {
      return 'n-a';
    }
    return $string;
  }

}
