<?php

namespace Drupal\datetime_testing;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Component\Datetime\DateTimePlus;

/**
 * A datetime object that extends DrupalDateTime() and thus DateTimePlus().
 *
 * This class allows for specifying the time to use a a reference when
 * strings as a datetime, insteading of always interpreting with reference to
 * the current time.
 *
 * The $settings array argument to the ::__contruct() method can be given a key
 * 'current_time'. If none is provided, \Drupal::time()->getCurrentTime() is
 * used.
 *
 * In this case TestDateTime('now)->getTimeStamp() will return the same value as \Drupal::time()->getCurrentTime(), which is not necessarily the same
 * value as the system time().
 */
class TestDateTime extends DrupalDatetime {

  /**
   * The current time.
   *
   * @var int
   */
  protected $currentTime;

  /**
   * An array of date parts for year, month & day.
   *
   * @var array
   */
  protected $dayParts = [
    'year' => 'Y',
    'month' => 'm',
    'day' => 'd',
  ];

  /**
   * An array of date parts for time.
   *
   * @var array
   */
  protected $timeParts = [
    'hour' => 'H',
    'minute' => 'i',
    'second' => 's',
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct($time = 'now', $timezone = NULL, $settings = []) {
    // Use the real current time if no substitute supplied.
    if (!isset($settings['current_time'])) {
      $settings['current_time'] = \Drupal::time()->getCurrentTime();
    }
    $this->currentTime = $settings['current_time'];

    // Instantiate the parent class.
    parent::__construct($time, $timezone, $settings);
  }

  /**
   * Get the current time.
   *
   * @return int
   *   A Unix timestamp.
   */
  protected function getCurrentTime() {
    return $this->currentTime;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareTime($time) {
    // Break the string into an array using the standard php parsing logic.
    $parsedDate = date_parse($time);
    if (!empty($parsedDate['errors'])) {
      $this->errors[] = $parsedDate['errors'];
      return;
    }

    // Prepare a date ignoring relative time information.
    $date = $this->prepareAbsoluteTime($parsedDate);

    // If the string contains relative time information, adjust the
    // reference date using that.
    if (isset($parsedDate['relative'])) {
      // Adjust the days first, because date_parse gives -7 days for past
      // weekdays like 'last Tuesday'.
      $interval = $this->prepareTimeInterval($parsedDate['relative'], $this->dayParts);
      $date->add($interval);
      // If a relative weekday is given, move the day forward to that.
      if (isset($parsedDate['relative']['weekday'])) {
        $this->setWeekday($date, $parsedDate['relative']['weekday']);
      }
      // Add time after weekday, because adding a large number of hours can
      // change the day, e.g. 'next Tuesday + 25 hours'.
      $interval = $this->prepareTimeInterval($parsedDate['relative'], $this->timeParts);
      $date->add($interval);
    }

    // \DateTime parses strings as timestamps if they begin with @.
    return '@' . $date->getTimestamp();
  }

  /**
   * Sets the weekday.
   *
   * @param \Datetime $date
   * @param string $weekday
   *
   * @return \Datetime
   */
  protected function setWeekday(&$date, $weekday) {
    $timeString = $date->format('H') . 'hours' . $date->format('i') . 'minutes' . $date->format('s') . 'seconds';
    // Use a hack to turn an integer into a string weekday.
    $weekdayString = date('l', strtotime("Sunday +{$weekday} days"));
    $date->modify("next $weekdayString");
    // Add the time back in, as modify sets it to zero.
    $date->modify($timeString);
    return $date;
  }

  /**
   * Constructs a datetime out of an array of parsed date parts.
   *
   * Missing values are filled in from Drupal's time, and relative date parts
   * are ignored.
   *
   * @param array $parsedDate
   *   An date parts array parsed from a string in the format of date_parse().
   *
   * @return \DateTime
   *   A datetime.
   */
  protected function prepareAbsoluteTime(array $parsedDate) {
    // If a timezone was supplied in the string then use that, otherwise
    // interpret time using the default time zone.
    $timezone = NULL;
    if ($parsedDate['is_localtime']) {
      if (isset($parsedDate['tz_id'])) {
        $timezone = $parsedDate['tz_id'];
      }
      else {
        $this->errors[] = "Time zone must be specified using a timezone name. See http://php.net/manual/en/timezones.php";
      }
    }
    $timezone = $this->prepareTimezone($timezone);

    // Fill in missing datetime information using the current time, formatted
    // using the same timezone that will later be used to intepret it.
    $current = DateTimePlus::createFromTimestamp($this->getCurrentTime(), $timezone);
    $filled = [];
    foreach ($this->dayParts as $part => $format) {
      $filled[$part] = $parsedDate[$part];
      // Fill in date based on current date.
      if ($filled[$part] === FALSE) {
        $filled[$part] = $current->format($format);
      }
    }
    foreach ($this->timeParts as $part => $format) {
      // Time parts are zero in case of 'today' or '2018-03-04 00:00' but FALSE
      // in case of '2018-03-03' or 'now' or '1 hour'. However, '2018-03-03'
      // should be taken as midnight, whereas 'now' or '+1 hour' should not.
      // Therefore only fill in time based on current time when the date is
      // not supplied. This leads to correctly interpreting '2018-03-03 +1 hour'
      // as '2018-03-03 00:00' plus one hour.
      $filled[$part] = $parsedDate[$part];
      // If a time part is not supplied, fill in from the current time only if
      // the day is also not supplied.
      if ($filled[$part] === FALSE) {
        if ($parsedDate['day'] === FALSE) {
          $filled[$part] = $current->format($format);
        }
        else {
          // Treat missing times to midnight, or exactly on the hour or minute.
          $filled[$part] = '0';
        }
      }
      // PHP date formats require a leading zero for minute and second, but
      // date_parse doesn't provide one.
      $filled[$part] = static::datePad($filled[$part]);
    }

    // Interpret the filled-in time using the timezone.
    $date = \DateTime::createFromFormat('Y-n-j-H-i-s', implode($filled, '-'), $timezone);
    return $date;
  }

  /**
   * Get a \DateInterval from an array of relative time parts.
   *
   * @param array $relative
   *   The 'relative' subarray from date_parse().
   * @param array $parts
   *   An array of date parts to consider, keyed by part name.
   *
   * @return \DateInterval
   *   A date interval object representing the relative time offset.
   */
  protected function prepareTimeInterval(array $relative, array $parts) {
    // Build a string describing a time interval, in the format
    // required by DateInterval:createFromString.
    $intervalString = '';
    foreach ($parts as $part => $format) {
      // Treat empty increments as zero increments.
      $increment = 0;
      if (isset($relative[$part]) && $relative[$part]) {
        $increment = $relative[$part];
      }
      $intervalString .= $increment . ' ' . $part . ' ';
    }
    $interval = \DateInterval::createFromDateString($intervalString);
    return $interval;
  }

}
