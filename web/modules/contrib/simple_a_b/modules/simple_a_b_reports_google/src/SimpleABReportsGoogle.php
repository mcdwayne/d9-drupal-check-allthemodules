<?php

namespace Drupal\simple_a_b_reports_google;

/**
 * Defines Report helper to push to Google Analytics.
 */
class SimpleABReportsGoogle {

  /**
   * Constant for the session id.
   *
   * @var string
   */
  private static $sessionId = "simple_a_b_reports_google";

  /**
   * Adds an incoming report to the report array.
   *
   * @param array $data
   *   An array of report data.
   *
   * @return bool
   *   returns if the data has been saved
   */
  public static function setReport(array $data = []) {

    // Load up any already set reports, if we cannot find
    // any create an empty array.
    $array = self::getReport();
    if (!$array || !is_array($array)) {
      $array = [];
    }

    // Add the data to the array.
    $array[] = $data;

    // Prepare the data for being saved into session.
    $array = self::prepareData($array);

    // Save the data into session.
    $request = \Drupal::request();
    $session = $request->getSession();
    $set = $session->set(self::$sessionId, $array);

    // Return its response.
    return $set;
  }

  /**
   * Returns all set reports.
   *
   * @return mixed|string
   *   Returns any data or false/null
   */
  public static function getReport() {

    // Load the reports from the session.
    $request = \Drupal::request();
    $session = $request->getSession();
    $get = $session->get(self::$sessionId);

    // Clean up the data so it can be read.
    $data = self::prepareData($get);

    // Return the data.
    return $data;
  }

  /**
   * Sets the report data back to an empty array.
   *
   * @return bool
   *   Returns status of if everything was removed.
   */
  public static function removeAllReports() {
    // Prepare the empty array of data.
    $data = self::prepareData([]);

    // Save the data into session.
    $request = \Drupal::request();
    $session = $request->getSession();
    $set = $session->set(self::$sessionId, $data);

    // Return the result.
    return $set;
  }

  /**
   * Checks if the data is serialize or not and converts it correctly.
   *
   * @param object $data
   *   Serialized or non serialized array.
   *
   * @return mixed|string
   *   Returns back an array or serialized data.
   */
  private static function prepareData($data) {
    // Check of serialized data.
    $is_serialize = (@unserialize($data) !== FALSE || $data == 'b:0;');

    // If it is serialized we should un-serialize it else serialize.
    if ($is_serialize) {
      $data = unserialize($data);
    }
    else {
      $data = serialize($data);
    }

    // Return the new data.
    return $data;
  }

}
