<?php

namespace Drupal\evergreen;

/**
 * Parse for expiry strings.
 */
class ExpiryParser {
  protected $validUnits = [
    'second' => 'seconds',
    'seconds' => 'seconds',
    'minute' => 'minutes',
    'minutes' => 'minutes',
    'hour' => 'hours',
    'hours' => 'hours',
    'day' => 'days',
    'days' => 'days',
    'week' => 'weeks',
    'weeks' => 'weeks',
  ];

  /**
   * Convert a number of seconds to seconds.
   */
  protected function convertSecondsToSeconds($seconds) {
    return intval($seconds);
  }

  /**
   * Convert a number of minutes to seconds.
   */
  protected function convertMinutesToSeconds($minutes) {
    return $minutes * 60;
  }

  /**
   * Convert a number of hours to seconds.
   */
  protected function convertHoursToSeconds($hours) {
    return $hours * 60 * 60;
  }

  /**
   * Convert a number of days to seconds.
   */
  protected function convertDaysToSeconds($days) {
    return $days * (60 * 60 * 24);
  }

  /**
   * Convert a number of weeks to seconds.
   */
  protected function convertWeeksToSeconds($weeks) {
    return $weeks * (60 * 60 * 24 * 7);
  }

  /**
   * Parse an expiry string into seconds.
   *
   * This function excepts strings like:
   *
   * - 300
   * - 300 seconds
   * - 30 minutes
   * - 3 days, 10 hours
   */
  public function parse($expiry, $debug = FALSE) {
    if (preg_match('/^\d+$/', $expiry)) {
      return intval($expiry);
    }

    $current_number = '';
    $current_unit = '';

    $seconds = 0;

    $len = strlen($expiry);
    for ($i = 0; $i < $len; $i++) {
      $char = $expiry[$i];
      if (preg_match('/^[0-9]$/', $char)) {
        $current_number .= $char;
        continue;
      }

      if ($char == ' ' || $char == ',') {
        if ($current_unit) {
          if ($debug) {
            var_dump($current_number, $current_unit);
          }
          $seconds = $this->updateSeconds($seconds, $current_number, $current_unit);
          $current_unit = $current_number = '';
        }
        continue;
      }

      $current_unit .= $char;
    }

    if ($current_unit) {
      if ($debug) {
        var_dump($current_number, $current_unit);
      }
      $seconds = $this->updateSeconds($seconds, $current_number, $current_unit);
    }

    return $seconds;
  }

  /**
   * Update the number of seconds by calling the unit specific function.
   */
  protected function updateSeconds($seconds, $current_number, $current_unit) {
    if (!in_array($current_unit, array_keys($this->validUnits))) {
      throw new \Exception('Invalid unit for expiry time: ' . $current_unit);
    }

    $value = $this->validUnits[$current_unit];
    $function = 'convert' . ucfirst($value) . 'ToSeconds';
    $seconds += call_user_func([$this, $function], $current_number);
    return $seconds;
  }

}
