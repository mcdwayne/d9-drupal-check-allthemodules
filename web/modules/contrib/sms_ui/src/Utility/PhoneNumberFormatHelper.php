<?php

/**
 * @file
 * Contains \Drupal\sms_ui\Utility\PhoneNumberFormatHelper;
 */

namespace Drupal\sms_ui\Utility;

/**
 * Helper method to format numbers to international standard.
 *
 * @todo Make this a service that can do multiple cleaning.
 */
class PhoneNumberFormatHelper {

  /**
   * Corrects phone number to international number format.
   */
  public static function formatNumber($number, $country_code) {
    $number = preg_replace('/[^0-9]/i', '', $number);
    if (empty($number)) return FALSE;

    // remove leading zeros
    $number = ltrim($number, '0');

    // Checks based on number length n > 7 and n < 15.
    if (strlen($number) < 7) {
      // Invalid number.
      return FALSE;
    }
    else if (strlen($number) < 11) {
      $number = $country_code . $number;
    }
    else if (strlen($number) > 15) {
      // Invalid number
      return FALSE;
    }
    else {
      // Remove zeros between country code and the rest of the number.
      $number = preg_replace("/^{$country_code}[0]+/", $country_code, $number);
    }

    return $number;
  }

  /**
   * Format numbers sent as a string of comma- or whitespace-delimited numbers.
   *
   * @param string $numbers
   *   The comma- or whitespace-delimited list of numbers.
   * @param string $country_code
   *   The country code to be used for formatting local numbers.
   *
   * @return array
   *   Array of correct numbers that were formatted, keyed by original number.
   */
  public static function formatNumbers($numbers, $country_code) {
    $return = [];
    foreach (static::splitNumbers($numbers) as $number) {
      if ($formatted = static::formatNumber($number, $country_code)) {
        $return[$number] = $formatted;
      }
    }
    return $return;
  }

  /**
   * Splits a string of comma- or whitespace-delimited phone numbers.
   *
   * @param string $numbers
   *   The comma- or whitespace-delimited list of numbers.
   * @param string $regex
   *   (optional) The regular expression for splitting. Defaults to whitespace
   *   and commas.
   * @param boolean $no_duplicates
   *   (optional) If true, duplicate numbers will be removed after splitting.
   *   Defaults to true.
   *
   * @return array
   *   An array of phone numbers split by comma and whitespace.
   */
  public static function splitNumbers($numbers, $no_duplicates = TRUE, $regex = '/[#\s,]+/') {
    // Turn the single 'number' string into a 'numbers' array. Remove duplicates
    // if specified.
    // @todo Which regex is better?
    $numbers = preg_split($regex, trim($numbers, " ,\n\r\t\0\x0B"));
    if ($no_duplicates) {
      $numbers = array_unique($numbers);
    }
    return $numbers;
  }

}
