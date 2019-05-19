<?php

namespace Drupal\time_field;

/**
 * Time class represents time of day.
 *
 * @package Drupal\time_field
 */
class Time {

  /**
   * Time hour.
   *
   * @var int
   */
  private $hour;

  /**
   * Time minute.
   *
   * @var int
   */
  private $minute;

  /**
   * Time second.
   *
   * @var int
   */
  private $second;

  /**
   * Create Time instance.
   *
   * @param int $hour
   *   Time hour.
   * @param int $minute
   *   Time minute.
   * @param int $second
   *   Time seconds.
   */
  public function __construct($hour = 0, $minute = 0, $second = 0) {
    self::assertInRange($hour, 0, 23);
    self::assertInRange($minute, 0, 59);
    self::assertInRange($second, 0, 59);
    $this->hour = $hour;
    $this->minute = $minute;
    $this->second = $second;
  }

  /**
   * Asserts that given value is between certain range.
   *
   * @param int $value
   *   Value to check.
   * @param int $from
   *   Lower bound of the assertion.
   * @param int $to
   *   Higher bound of the assertion.
   */
  private static function assertInRange($value, $from, $to) {
    if ($value < $from || $value > $to) {
      throw new \InvalidArgumentException('Provided value is out of range.');
    }
  }

  /**
   * Base datetime object time functions on it.
   *
   * @return \DateTime
   *   Base datetime object to use time on it
   */
  private static function baseDateTime() {
    return new \DateTime('2012-01-01 00:00:00');
  }

  /**
   * Number of hours.
   *
   * @return int
   *   Number of hours
   */
  public function getHour() {
    return $this->hour;
  }

  /**
   * Number of seconds.
   *
   * @return int
   *   Number of seconds
   */
  public function getSecond() {
    return $this->second;
  }

  /**
   * Number of minutes.
   *
   * @return int
   *   Number of minutes
   */
  public function getMinute() {
    return $this->minute;
  }

  /**
   * Number of seconds passed through midnight.
   *
   * @return int
   *   Number of seconds passed through midnight
   */
  public function getTimestamp() {
    $value = $this->hour * 60 * 60;
    $value += $this->minute * 60;
    $value += $this->second;
    return $value;
  }

  /**
   * Creates Time object from timestamp.
   *
   * @param int $timestamp
   *   Number of seconds passed through midnight
   *   must be between 0 and 86400.
   *
   * @return \Drupal\time_field\Time
   *   Time object created based on timestamp
   */
  public static function createFromTimestamp($timestamp) {
    self::assertInRange($timestamp, 0, 86400);
    $time = self::baseDateTime();
    $time->setTimestamp($time->getTimestamp() + $timestamp);
    return new self($time->format('H'), $time->format('i'), $time->format('s'));
  }

  /**
   * Create Time object based on html5 formatted string.
   *
   * @param string $string
   *   Time string eg `12:30:20` or `12:30`.
   *
   * @return \Drupal\time_field\Time
   *   Time object created html5 formatted string
   */
  public static function createFromHtml5Format($string) {
    if (!$string) {
      return new self();
    }
    $inputs = explode(':', $string);
    if (count($inputs) === 2) {
      $inputs[] = 0;
    }
    list ($hour, $minute, $seconds) = $inputs;
    return new self($hour, $minute, $seconds);
  }

  /**
   * Format Time.
   *
   * @param string $format
   *   Format string.
   *
   * @return string
   *   Formatted time eg `12:30 AM`
   */
  public function format($format = 'h:i a') {
    $time = self::baseDateTime();
    $time->setTimestamp($time->getTimestamp() + $this->getTimestamp());
    return $time->format($format);
  }

  /**
   * Format for widget.
   *
   * @param bool $show_seconds
   *   (Optional) Whether to include the seconds in the output regardless
   *     of the current time value. Defaults to TRUE.
   *   This is to ensure the option to adjust seconds is not shown in the
   *     widget when we don't want it to.
   *
   * @return string
   *   Formatted time eg `23:12:00`
   */
  public function formatForWidget($show_seconds = TRUE) {
    $time = self::baseDateTime();
    $time->setTimestamp($time->getTimestamp() + $this->getTimestamp());
    // If we're showing seconds, include the seconds in the output.
    if ($show_seconds) {
      return $time->format('H:i:s');
    }
    // Otherwise, exclude the seconds in the output.
    return $time->format('H:i');
  }

  /**
   * DateTime with attached time to it.
   *
   * @param \DateTime $dateTime
   *   Datetime to attach time to it.
   *
   * @return \DateTime
   *   Datetime with attached time
   */
  public function on(\DateTime $dateTime) {
    $instance = new \DateTime();
    $instance->setTimestamp($dateTime->getTimestamp());
    $instance->setTime(0, 0, 0);
    $instance->setTimestamp($instance->getTimestamp() + $this->getTimestamp());
    return $instance;
  }

}
