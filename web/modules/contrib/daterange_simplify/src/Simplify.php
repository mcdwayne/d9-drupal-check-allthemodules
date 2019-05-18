<?php
namespace Drupal\daterange_simplify;

// this is a hack -- the module should be installed by composer with dependencies
// but if these are not loaded, they're included in the module distribution
if (!class_exists('OpenPsa\Ranger\Ranger')) {
  require_once __DIR__ . '/../vendor/autoload.php';
}

use OpenPsa\Ranger\Ranger;
use DateTime;
use DateTimeZone;

/**
 * Daterange simplification tasks.
 */
class Simplify {

  /**
   * Return allowed formats.
   *
   * See http://php.net/manual/en/class.intldateformatter.php.
   *
   * @param bool $restrict_intl
   *   Limit options available in the absence of intl support.
   *
   * @return array
   *   Possible options.
   */
  public static function getAllowedFormats($restrict_intl = FALSE) {
    if ($restrict_intl) {
      return ['none', 'short'];
    }
    return ['none', 'full', 'long', 'medium', 'short'];
  }

  /**
   * Helper function: return an enumerated constant for the format.
   */
  protected static function getDateFormat($format) {
    switch ($format) {
      case 'none':
        return \IntlDateFormatter::NONE;

      case 'full':
        return \IntlDateFormatter::FULL;

      case 'long':
        return \IntlDateFormatter::LONG;

      case 'medium':
        return \IntlDateFormatter::MEDIUM;

      case 'short':
        return \IntlDateFormatter::SHORT;
    }
    return \IntlDateFormatter::MEDIUM;
  }

  /**
   * Simplify a date range.
   */
  public static function daterange(DateTime $start, DateTime $end, $date_format = 'medium', $time_format = 'short', $range_separator = null, $date_time_separator = null, $locale = 'en') {
    $date_format = Simplify::getDateFormat($date_format);
    $time_format = Simplify::getDateFormat($time_format);

    $ranger = new Ranger($locale);
    $ranger
      ->setDateType($date_format)
      ->setTimeType($time_format);
    if (!is_null($date_time_separator)) {
      $ranger->setDateTimeSeparator($date_time_separator);
    }
    if (!is_null($range_separator)) {
      $ranger->setRangeSeparator($range_separator);
    }
    if (is_numeric($start)) {
      $start = date('c', $start);
    }
    if (is_numeric($end)) {
      $end = date('c', $end);
    }
    if ($start instanceof \DateTime) {
      $start = $start->format('c');
    }
    if ($end instanceof \DateTime) {
      $end = $end->format('c');
    }
    if (empty($end)) {
      $end = $start;
    }
    return $ranger->format($start, $end);
  }

  /**
   * Correct for user timezone, convert to DateTime.
   */
  public function prepDate($datetime) {
    $tz = drupal_get_user_timezone();
    if (in_array($tz, timezone_identifiers_list())) {
      $date = new DateTime($datetime, new DateTimeZone('UTC'));
      $date->setTimezone(new DateTimeZone($tz));
    }
    else {
      // Bail out.
      $date = new DateTime($datetime);
    }
    return $date;
  }

}
