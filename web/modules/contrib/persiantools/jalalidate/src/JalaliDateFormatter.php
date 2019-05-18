<?php

/**
 * @file
* Contains \Drupal\jalalidate\JalaliDate.
*/

namespace Drupal\jalalidate;

use Drupal\Core\Datetime\DateFormatter;

/**
 * Overrides the drupal date service to provide a jalali date.
 */
class JalaliDateFormatter extends DateFormatter {

  /**
   * {@inheritdoc}
   */
  public function format($timestamp, $type = 'medium', $format = '', $timezone = NULL, $langcode = NULL) {
    if (empty($langcode)) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
    }

    if ($langcode != "fa" || !class_exists('IntlDateFormatter')) {
      return parent::format($timestamp, $type, $format, $timezone, $langcode);
    }

    if (!isset($timezone)) {
      $timezone = date_default_timezone_get();
    }
    // Store DateTimeZone objects in an array rather than repeatedly
    // constructing identical objects over the life of a request.
    if (!isset($this->timezones[$timezone])) {
      $this->timezones[$timezone] = timezone_open($timezone);
    }

    // If we have a non-custom date format use the provided date format pattern.
    if ($date_format = $this->dateFormat($type, $langcode)) {
      switch ($date_format) {
        case 'short':
          $format = 'Y/M/d';
          break;
        case 'medium':
          $format = 'Y/M/d - H:m';
          break;
        case 'long':
          $format = 'EEEE Y/M/d - H:m';
          break;
      }
    }

    // Fall back to medium if a format was not found.
    if (empty($format)) {
      $format = 'EEEE Y/M/d - H:m';
    }

    $date = new \IntlDateFormatter("fa_IR@calendar=persian", \IntlDateFormatter::FULL, \IntlDateFormatter::SHORT, $timezone, \IntlDateFormatter::TRADITIONAL);
    $date->setPattern($format);
    return $date->format(intval($timestamp));
  }
}
