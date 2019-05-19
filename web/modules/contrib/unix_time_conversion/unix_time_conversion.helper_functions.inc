<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Helper function to get array in certain range.
 *
 * @param int $minimum
 *   The minimum integer value.
 * @param int $maximum
 *   The maximum integer value.
 *
 * @return array
 *   The array of integers.
 */
function unix_time_conversion_get_time_in_range($minimum, $maximum) {
  // Range of numbers.
  $range_of_numbers = range($minimum, $maximum);
  // Form array to return.
  $return_array = array();
  // Loop from min to max value & form an array.
  foreach ($range_of_numbers as $number) {
    $element = ($number < 10) ? ("0" . $number) : ($number);
    $return_array[$element] = $element;
  }
  // Return the array.
  return $return_array;
}

/**
 * Helper function to get date from timestamp.
 *
 * @param string $timestamp
 *   The formatted date to return to user.
 *
 * @return string
 *   The date string.
 */
function unix_time_conversion_get_date_from_timestamp($timestamp) {

  $config = \Drupal::config('unix_time_conversion.settings');
  $date_format = $config->get('unix_time_conversion_time_to_date_output_format');
  // Get the date.
  $date = gmdate($date_format, $timestamp);
  // Output formation.
  $output = "<span class='unix_time_conversion_output_date'>";
  $output .= $date;
  $output .= "</span>";
  // Return the output.
  return $output;
}

/**
 * Helper function to get timestamp from date & time.
 *
 * @param array $date
 *   The date.
 * @param array $time
 *   The time.
 *
 * @return string
 *   The timestamp.
 */
function unix_time_conversion_get_timestamp_from_date_and_time($date, $time) {
  $date = explode('-', $date);
  $month = $date[1];
  $day = $date[2];
  $year = $date[0];
  $hour = $time['time_container']['hours'];
  $minute = $time['time_container']['minutes'];
  $second = $time['time_container']['seconds'];
  // Calculate timestamp.
  $timestamp = gmmktime($hour, $minute, $second, $month, $day, $year);
  // Output formation.
  $output = "<span class='unix_time_conversion_output_timestamp'>";
  $output .= $timestamp;
  $output .= "</span>";
  // Return the output.
  return $output;
}
