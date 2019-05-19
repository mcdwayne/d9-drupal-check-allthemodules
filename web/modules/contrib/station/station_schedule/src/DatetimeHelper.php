<?php

/**
 * @file
 * Contains \Drupal\station_schedule\DatetimeHelper.
 */

namespace Drupal\station_schedule;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * @todo.
 */
class DatetimeHelper {

  /**
   * Computes time information for a minute in the week.
   *
   * @param int $minutes
   *   Integer specifying minutes since midnight on Sunday.
   *
   * @return array
   *   An array with the following keys:
   *     'w'       - Day of week (0-6).
   *     'G'       - 24 hour.
   *     'g'       - 12 hour.
   *     'H'       - 24 hour, 0 padded.
   *     'h'       - 12 hour, 0 padded.
   *     'i'       - minutes, 0 padded.
   *     'time'    - hour and minutes acording to 12/24 setting.
   *     'minutes' - minutes since midnight Sunday.
   *     'a'       - am/pm.
   */
  public static function deriveTimeFromMinutes($minutes) {
    $minutes_per_day = 60 * 24;
    $min = $minutes % 60;
    $day = (int) (($minutes) / $minutes_per_day);
    $hour24 = (int) (($minutes % $minutes_per_day) / 60);
    if (!($hour12 = $hour24 % 12)) {
      $hour12 = 12;
    }
    $i = str_pad($min, 2, '0', STR_PAD_LEFT);
    $h = str_pad($hour12, 2, '0', STR_PAD_LEFT);
    $time = $hour12 . (($min == 0) ? '' : ":$i");
    $a = ($hour24 > 11) ? 'pm' : 'am';
    return [
      'w' => $day,
      'G' => $hour24,
      'g' => $hour12,
      'H' => str_pad($hour24, 2, '0', STR_PAD_LEFT),
      'h' => $h,
      'i' => $i,
      'time' => $time,
      'minutes' => $minutes,
      'a' => $a,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function deriveMinutesFromTime($timestamp = 'now') {
    $date_time = new DrupalDateTime($timestamp);
    list($day, $hour, $minute) = explode(' ', $date_time->format('w G i'));
    return (($day * 24) + $hour) * 60 + $minute;
  }

  /**
   * Formats a range of minutes into a hour string, e.g. "1am-3pm".
   *
   * @param int $start
   *   Integer specifying minutes.
   * @param int $finish
   *   Integer specifying minutes.
   *
   * @return string
   *   Formatted string.
   */
  public static function hourRange($start, $finish) {
    $start = static::deriveTimeFromMinutes($start);
    $finish = static::deriveTimeFromMinutes($finish);
    $format_params = [
      '@stime' => $start['time'], '@sampm' => $start['a'],
      '@ftime' => $finish['time'], '@fampm' => $finish['a'],
    ];

    if ($start['a'] == $finish['a']) {
      return static::t('@stime-@ftime@sampm', $format_params);
    }
    else {
      return static::t('@stime@sampm-@ftime@fampm', $format_params);
    }
  }

  /**
   * Wraps t().
   */
  public static function t($string, array $args = [], array $options = []) {
    return \Drupal::translation()->translate($string, $args, $options);
  }

}
